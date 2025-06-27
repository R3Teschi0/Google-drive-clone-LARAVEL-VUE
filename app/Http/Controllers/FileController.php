<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToFavouritesRequest;
use App\Http\Requests\FileActionRequest;
use App\Http\Requests\FileVersionActionRequest;
use App\Http\Requests\FileVersionRequest;
use App\Http\Requests\ModifyFileRequest;
use App\Http\Requests\restoreVersion;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Resources\FileResource;
use App\Http\Resources\FileVersionResource;
use App\Mail\shareFilesMail;
use App\Models\File;
use App\Models\FileShare;
use App\Models\FileVersion;
use App\Models\FileVersionShare;
use App\Models\StarredFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FileController extends Controller
{
    public function myFiles(Request $request, ?string $folder = null)
    {
        $search = $request->get('search');
        $favourites = (int)$request->get('favourites');

        if ($folder) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder)
                ->firstOrFail();
        }
        if (!$folder) {
            $folder = $this->getRoot();
        }

        $ids = File::query()->where('created_by', Auth::id())->where('parent_id', $folder->id)->pluck('id')->toArray();

        $query = FileVersion::query()->whereRaw('file_versions.version = (
                        SELECT MAX(fv.version)
                        FROM file_versions fv
                        WHERE fv.file_id = file_versions.file_id
            )')
            ->whereIn('file_id', $ids)
            ->orderByRaw('(SELECT is_folder FROM files WHERE files.id = file_versions.file_id) DESC')
            ->orderBy('file_versions.created_at', 'desc')
            ->with('file');

        // $query = FileVersion::query()
        //     ->with('file')
        //     ->whereRaw('file_versions.version = (
        //                 SELECT MAX(fv.version)
        //                 FROM file_versions fv
        //                 WHERE fv.file_id = file_versions.file_id
        //     )')
        //     ->whereHas(
        //         'file',
        //         function ($q) use ($folder) {
        //             $q->where('created_by', Auth::id())
        //                 ->where('parent_id', $folder->id);
        //         }
        //     )->join('files', 'files.id', '=', 'file_versions.file_id')
        //     ->orderBy('files.is_folder', 'desc')  // Cartelle prima
        //     ->orderBy('file_versions.created_at', 'desc');  // Poi i data

        if ($search) {
            $ids = $this->getAllFileIdsRecursive($folder->id);
            $query = FileVersion::query()
                ->whereIn('file_id', $ids)
                ->with('file')
                ->where('original_name', 'like', "%$search%");
        }

        if ($favourites === 1) {
            $favouriteIds = DB::table('starred_files')
                ->where('user_id', Auth::id())
                ->pluck('file_id');
            $query = FileVersion::query()
                ->whereRaw('file_versions.version = (
                    SELECT MAX(fv.version)
                    FROM file_versions fv
                    WHERE fv.file_id = file_versions.file_id
                )')
                ->whereIn('file_id', $favouriteIds);
        }

        $files = $query->paginate(10);

        $files = FileVersionResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        $ancestors = FileResource::collection([...$folder->ancestors, $folder])->resolve();

        $folder = FileResource::make($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function myFileVersions(Request $request, FileVersion $file)
    {
        $search = $request->get('search');

        $query = FileVersion::query()
            ->select('file_versions.*')
            ->where('file_id', '=', $file->file_id)
            ->orderBy('version', 'desc');

        if ($search) {
            $query->where('original_name', 'like', "%$search%");
        }

        $files = $query->paginate(10);

        $file_path = File::where('id', $file->file_id)->first()?->path;

        $files = FileVersionResource::collection($files);

        $file_base_parent_id = File::where('id', $file->file_id)->first()->parent_id;
        $parent = File::where('id', $file_base_parent_id)->first();

        return Inertia::render('MyFileVersions', compact('files', 'file_path', 'file', 'parent'));
    }

    public function mySharedVersions(Request $request, FileVersion $file)
    {
        $fileId = $file->file_id;

        //dd($request->parent_id);

        $sharedVersionIds = FileVersionShare::query()
            ->where('user_id', Auth::id())
            ->pluck('version_id')
            ->toArray();

        $query = FileVersion::query()
            ->where('file_id', $fileId)
            ->whereIn('id', $sharedVersionIds)
            ->orderBy('version', 'desc');

        $files = $query->paginate(10);

        $file_path = File::where('id', $file->file_id)->first()?->path;

        $files = FileVersionResource::collection($files);

        return Inertia::render('MySharedVersions', compact('files', 'file_path', 'file'));
    }

    public function restoreVersion(restoreVersion $request)
    {
        $fileVersionId = $request->validated()['file_id'];
        $versionToRestore = FileVersion::findOrFail($fileVersionId);
        $fileId = $versionToRestore->file_id;
        $maxVersion = FileVersion::where('file_id', $fileId)->max('version');
        $currentVersion = $versionToRestore->version;

        if (!($currentVersion === $maxVersion)) {
            FileVersion::where('file_id', $fileId)
                ->where('version', '>', $currentVersion)
                ->decrement('version');
        }

        $versionToRestore->version = $maxVersion;
        $versionToRestore->save();
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();

        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);

        //creation of the file-version model
        $version = new FileVersion();
        $version->file_id = $file->id;
        $version->mime = 'folder';
        $version->size = 0;
        $version->version = 1;
        $version->hash = Hash::make($file);
        $version->extension = '';
        $version->original_name = $file->name;
        $version->uuid = Str::uuid();

        $version->save();
    }

    public function store(StoreFileRequest $request)
    {
        $data = $request->validated();

        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {

            foreach ($data['files'] as $file) {

                $path = $file->store('/files/' . $user->id);

                //creation of the file model
                $model = new File();
                $model->is_folder = false;
                $model->name = $file->getClientOriginalName();
                $model->mime = $file->getMimeType();
                $model->size = $file->getSize();
                $parent->appendNode($model);

                //creation of the file-version model
                $version = new FileVersion();
                $version->file_id = $model->id;
                $version->storage_path = $path;
                $version->mime = $model->mime;
                $version->size = $model->size;
                $version->version = 1;
                $version->hash = Hash::make($file);
                $version->extension = $file->getClientOriginalExtension();
                $version->original_name = $model->name;
                $version->uuid = Str::uuid();

                $version->save();
            }
        }
    }

    public function modify(ModifyFileRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();
        $fileModifiedId = $request->fileModifiedId;

        $file = $data['files'];

        $path = $file->store('/files/' . $user->id);

        //creation of the file-version model
        $version = new FileVersion();
        $version->file_id = $fileModifiedId;
        $version->mime = $file->getMimeType();
        $version->storage_path = $path;
        $version->size = $file->getSize();
        $version->version = FileVersion::where('file_id', $fileModifiedId)->max('version') + 1;
        $version->hash = Hash::make($file);
        $version->extension = $file->getClientOriginalExtension();
        $version->original_name = $file->getClientOriginalName();
        $version->uuid = Str::uuid();
        $version->save();
    }


    public function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {

                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;
                $parent->appendNode($folder);

                //creation of the file-version model
                $version = new FileVersion();
                $version->file_id = $folder->id;
                $version->mime = 'folder';
                $version->size = 0;
                $version->version = 1;
                $version->hash = Hash::make($folder);
                $version->extension = '';
                $version->original_name = $folder->name;
                $version->uuid = Str::uuid();

                $version->save();

                $this->saveFileTree($file, $folder, $user);
            } else {

                $path = $file->store('/files/' . $user->id);

                $model = new File();
                $model->is_folder = false;
                $model->name = $file->getClientOriginalName();
                $model->mime = $file->getMimeType();
                $model->size = $file->getSize();
                $parent->appendNode($model);

                //creation of the file-version model
                $version = new FileVersion();
                $version->file_id = $model->id;
                $version->storage_path = $path;
                $version->mime = $model->mime;
                $version->size = $model->size;
                $version->version = 1;
                $version->hash = Hash::make($file);
                $version->extension = $file->getClientOriginalExtension();
                $version->original_name = $model->name;
                $version->uuid = Str::uuid();

                $version->save();
            }
        }
    }

    public function destroy(FileActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $ids = $this->getAllFileToBeRemovedIdsRecursive($parent->id);
            $files = File::where('created_by', Auth::id())->whereIn('id', $ids)->get();
            $fileVersions = FileVersion::whereIn('file_id', $ids)->get();
        } else {
            $ids = $data['ids'];
            $ids = $this->getFileToBeRemovedIdsRecursive($parent->id, $ids);
            $files = File::where('created_by', Auth::id())->whereIn('id', $ids)->get();
            $fileVersions = FileVersion::whereIn('file_id', $ids)->get();
        }

        if ($files) {
            foreach ($files as $file) {
                $file->moveToTrash();
            }
        }

        foreach ($fileVersions as $version) {
            $version->moveToTrash();
        }


        return to_route('myFiles', ['folder' => $parent->path]);
    }

    // public function moveToTrash()
    // {

    //     $this->deleted_at = Carbon::now();
    //     return $this->save();
    // }

    public function restore(TrashFilesRequest $request)
    {
        $data = $request->validated();

        if ($data['all']) {
            $ids = FileVersion::onlyTrashed()
                ->whereRaw('version = (
                            SELECT MAX(fv2.version)
                            FROM file_versions fv2
                            WHERE fv2.file_id = file_versions.file_id
                            AND fv2.deleted_at IS NOT NULL
                            )')
                ->pluck('id')->toArray();
        } else {
            $ids =  $data['ids'] ?? [];
        }

        $versions = FileVersion::onlyTrashed()
            ->whereIn('id', $ids)
            ->get();

        $fileIds = $versions->pluck('file_id')->unique()->toArray();

        File::withTrashed()
            ->whereIn('id', $fileIds)
            ->restore();

        FileVersion::withTrashed()
            ->whereIn('file_id', $fileIds)
            ->restore();

        return to_route('trash');
    }

    public function deleteForever(TrashFilesRequest $request)
    {
        $data = $request->validated();

        if ($data['all']) {
            $ids = FileVersion::onlyTrashed()
                ->whereRaw('version = (
                            SELECT MAX(fv2.version)
                            FROM file_versions fv2
                            WHERE fv2.file_id = file_versions.file_id
                            AND fv2.deleted_at IS NOT NULL
                            )')
                ->pluck('id')->toArray();
        } else {
            $ids =  $data['ids'] ?? [];
        }

        $versions = FileVersion::onlyTrashed()
            ->whereIn('id', $ids)
            ->get();

        $fileIds = $versions->pluck('file_id')->unique()->toArray();


        FileVersion::withTrashed()
            ->whereIn('file_id', $fileIds)
            ->get()->each->forceDelete();

        File::withTrashed()
            ->whereIn('id', $fileIds)
            ->get()->each->forceDelete();
        return to_route('trash');
    }

    // public function createZip($files): string
    // {
    //     $zipPath = 'zip/' . Str::random() . '.zip';
    //     $publicPath = "public/$zipPath";

    //     if (!is_dir(dirname($publicPath))) {
    //         Storage::makeDirectory(dirname($publicPath));
    //     }

    //     $zipFile = Storage::path($publicPath);

    //     $zip = new \ZipArchive();

    //     if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
    //         $this->addFilesToZip($zip, $files);
    //     }

    //     $zip->close();

    //     return asset(Storage::url($zipPath));
    // }

    public function trash(Request $request)
    {
        $search = $request->get('search');

        $ids = File::query()->where('created_by', Auth::id())->onlyTrashed()->pluck('id')->toArray();

        $query = FileVersion::withTrashed()->whereRaw('file_versions.version = (
                        SELECT MAX(fv.version)
                        FROM file_versions fv
                        WHERE fv.file_id = file_versions.file_id
            )')
            ->whereIn('file_id', $ids)
            ->with('file');

        if ($search) {
            $query->where('original_name', 'like', "%$search%");
        }

        $files = $query->paginate(10);

        $files = FileVersionResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('Trash', compact('files'));
    }

    public function addTofavourite(AddToFavouritesRequest $request)
    {
        $data = $request->validated();

        $id = $data['id'];
        $file = File::find($id);
        $user_id = Auth::id();

        $starredFile = StarredFile::query()->where('file_id', $file->id)->where('user_id', $user_id)->first();

        if ($starredFile) {
            $starredFile->delete();
        } else {
            StarredFile::create([
                'file_id' => $file->id,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->back();
    }

    function share(ShareFilesRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $email = $data['email'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all & empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        if ($all) {
            $ids = $this->getAllFileIdsRecursive($parent->id);
        } else {
            $ids = $this->getFileIdsRecursive($parent->id, $ids);
        }

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return redirect()->back();
        }

        $data = [];

        $files = File::whereIn('id', $ids)->where('created_by', Auth::id())->get();

        $existingFileIds = FileShare::query()->whereIn('file_id', $ids)->where('user_id', $user->id)->get()->keyBy('file_id');
        foreach ($files as $file) {
            if (!$existingFileIds->has($file->id)) {

                $data[] = [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            $versions = FileVersion::where('file_id', $file->id)->pluck('id')->toArray();

            $sharedVersionIds = FileVersionShare::query()
                ->where('user_id', $user->id)
                ->whereIn('version_id', $versions)
                ->pluck('version_id')
                ->toArray();

            $newVersions = array_diff($versions, $sharedVersionIds);

            $versionData = [];
            foreach ($newVersions as $versionId) {
                $versionData[] = [
                    'version_id' => $versionId,
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($versionData)) {
                FileVersionShare::insert($versionData);
            }
        }

        if (!empty($data)) {
            FileShare::insert($data);
        }
        Mail::to($user)->send(new shareFilesMail($user, Auth::user(), $files));

        return redirect()->back();
    }

    function sharedWithMe(Request $request)
    {
        $search = $request->get('search');

        $parent_id = File::where('created_by', Auth::id())->whereNull('parent_id')->first()->id;

        $fileIds = DB::table('file_shares')
            ->join('files', 'files.id', '=', 'file_shares.file_id')
            ->where('file_shares.user_id', Auth::id())
            ->pluck('files.id')
            ->toArray();

        $query = FileVersion::query()
            ->whereRaw('file_versions.version = (
        SELECT MAX(fv.version)
        FROM file_versions fv
        WHERE fv.file_id = file_versions.file_id
        )')
            ->whereIn('file_id', $fileIds)
            ->with('file');

        $files = $query->paginate(10);

        $files = FileVersionResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedWithMe', compact('files', 'parent_id'));
    }

    function sharedByMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::query()
        ->select('files.*')
        ->join('file_shares', 'file_shares.file_id', '=', 'files.id')
        ->where('files.created_by', Auth::id())
        ->groupBy('files.id');

        $files = $query->paginate(10);

        $files = FileVersionResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedByMe', compact('files'));
    }

    public function download(FileActionRequest $request)
    {
        $data = $request->validated();
        $parent_id = $request->parent_id;
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        if ($all) {
            $ids = $this->getAllFileIdsRecursive($parent->id);
        } else {
            $ids = $this->getFileIdsRecursive($parent->id, $ids);
        }

        $latestVersions = DB::table('file_versions')
            ->select('file_id', DB::raw('MAX(version) as max_version'))
            ->whereIn('file_id', $ids)
            ->groupBy('file_id');

        $versionIds = FileVersion::joinSub($latestVersions, 'lv', function ($join) {
            $join->on('file_versions.file_id', '=', 'lv.file_id')
                ->on('file_versions.version', '=', 'lv.max_version');
        })
            ->pluck('file_versions.id')
            ->toArray();

        if ($all && count($ids) !== 1) {
            $fileData = null;
            $files = FileVersion::query()->whereIn('id', $versionIds)->get();
            $url = $this->createZip($files);
            $filename = $parent->name . '.zip';
        } else {
            [$url, $filename, $fileData] = $this->getDownloadUrl($versionIds, $parent->name);
        }

        if ($fileData !== null) {
            // fileData contiene ['base64' => ..., 'extension' => ...]
            return response()->json([
                'file' => $fileData['base64'],
                'filename' => $filename,
                'extension' => $fileData['extension'],
            ]);
        } else {
            // URL normale (zip o cartelle)
            return response()->json([
                'url' => $url,
                'filename' => $filename,
            ]);
        }
    }

    public function downloadVersion(FileVersionActionRequest $request)
    {
        $data = $request->validated();
        $file_base_parent_id = File::where('id', $data['file_base_id'])->first()->parent_id;
        $parent = File::where('id', $file_base_parent_id)->first();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        if ($all) {
            $fileData = null;
            $file_base_id = $data['file_base_id'];
            $files = FileVersion::where('file_id', $file_base_id)->get();
            $url = $this->createZip($files);
            $filename = $parent->name . '.zip';
        } else {
            [$url, $filename, $fileData] = $this->getDownloadUrl($ids, $parent->name);
        }

        if ($fileData !== null) {
            return response()->json([
                'file' => $fileData['base64'],
                'filename' => $filename,
                'extension' => $fileData['extension'],
            ]);
        } else {
            return response()->json([
                'url' => $url,
                'filename' => $filename,
            ]);
        }
    }

    private function getAllFileIdsRecursive($parentId)
    {
        $fileIds = [];

        $children = File::where('parent_id', $parentId)->get();

        foreach ($children as $child) {
            if ($child->is_folder) {
                $fileIds = array_merge($fileIds, $this->getAllFileIdsRecursive($child->id));
            } else {
                // Se è un file, aggiungilo
                $fileIds[] = $child->id;
            }
        }

        return $fileIds;
    }

    private function getFileIdsRecursive($parentId, $ids)
    {
        $fileIds = [];

        $children = File::whereIn('id', $ids)->get();

        foreach ($children as $child) {
            if ($child->is_folder) {
                $fileIds = array_merge($fileIds, $this->getAllFileIdsRecursive($child->id));
            } else {
                // Se è un file, aggiungilo
                $fileIds[] = $child->id;
            }
        }

        return $fileIds;
    }

    private function getAllFileToBeRemovedIdsRecursive($parentId)
    {
        $fileIds = [];

        $children = File::where('parent_id', $parentId)->get();

        foreach ($children as $child) {
            if ($child->is_folder) {
                $fileIds[] = $child->id;
                $fileIds = array_merge($fileIds, $this->getAllFileIdsRecursive($child->id));
            } else {
                // Se è un file, aggiungilo
                $fileIds[] = $child->id;
            }
        }

        return $fileIds;
    }

    private function getFileToBeRemovedIdsRecursive($parentId, $ids)
    {
        $fileIds = [];

        $children = File::where('parent_id', $parentId)->whereIn('id', $ids)->get();

        foreach ($children as $child) {
            if ($child->is_folder) {
                $fileIds[] = $child->id;
                $fileIds = array_merge($fileIds, $this->getAllFileIdsRecursive($child->id));
            } else {
                // Se è un file, aggiungilo
                $fileIds[] = $child->id;
            }
        }

        return $fileIds;
    }


    private function getDownloadUrl(array $ids, $zipName)
    {
        if (count($ids) === 1) {
            $file = FileVersion::find($ids[0]);
            if ($file->is_folder) {
                if ($file->children->count() === 0) {
                    return [
                        'message' => 'The folder is empty'
                    ];
                }

                $url = $this->createZip($file->children);
                $filename = $file->original_name . '.zip';
                $file = null;
            } else {

                $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
                if ($file->uploaded_on_cloud) {
                    $content = Storage::get($file->storage_path);
                } else {
                    $content = Storage::disk('local')->get($file->storage_path);
                }

                $base64 = base64_encode($content);
                $filename = $file->original_name;

                return [null, $filename, [
                    'base64' => $base64,
                    'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                ]];

                Log::debug("Getting file content. File:  " . $file->storage_path) . ". Content: " .  intval($content);
                $success = Storage::disk('public')->put($dest, $content);
                Log::debug('Inserted in public disk. "' . $dest . '". Success: ' . intval($success));
                $url = asset(Storage::disk('public')->url($dest));
                Log::debug("Logging URL " . $url);
            }
        } else {
            $files = FileVersion::query()->whereIn('id', $ids)->get();
            $url = $this->createZip($files);

            $filename = $zipName . '.zip';
            $file = null;
        }

        return [$url, $filename, $file];
    }

    public function createZip($files): string
    {
        $zipPath = 'zip/' . Str::random() . '.zip';
        $publicPath = "$zipPath";

        if (!is_dir(dirname($publicPath))) {
            Storage::disk('public')->makeDirectory(dirname($publicPath));
        }

        $zipFile = Storage::disk('public')->path($publicPath);

        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }

        $zip->close();

        return asset(Storage::disk('local')->url($zipPath));
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->original_name . '/');
            } else {
                $localPath = Storage::disk('local')->path($file->storage_path);
                if ($file->uploaded_on_cloud == 1) {
                    $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
                    $content = Storage::get($file->storage_path);
                    Storage::disk('public')->put($dest, $content);
                    $localPath = Storage::disk('public')->path($dest);
                }

                $zip->addFile($localPath, $ancestors . $file->original_name);
            }
        }
    }


    public function downloadSharedWithMe(FileActionRequest $request)
    {
        $data = $request->validated();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        $zipName = 'shared_with_me';
        if ($all) {
            $files = File::getSharedWithMe()->get();
            $url = $this->createZip($files);
            $filename = $zipName . '.zip';
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    public function downloadSharedByMe(FileActionRequest $request)
    {
        $data = $request->validated();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        $zipName = 'shared_by_me';
        if ($all) {
            $files = File::getSharedByMe()->get();
            $url = $this->createZip($files);
            $filename = $zipName . '.zip';
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    private function getRoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }
}
