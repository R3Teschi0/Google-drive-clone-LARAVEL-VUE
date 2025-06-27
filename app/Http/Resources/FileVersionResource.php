<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_id' => $this->file_id,
            'original_name' => $this->original_name,
            'storage_path' => $this->storage_path,
            'mime' => $this->mime,
            "size" => $this->get_file_size(),
            'version' => $this->version,
            'extension' => $this->extension,
            "created_at" => $this->created_at->diffForHumans(),
            "updated_at" => $this->updated_at->diffForHumans(),

            'path' => $this->file->path,
            'is_folder'=> $this->file->is_folder,
            'owner' => $this->file->owner,
            'is_favourite' =>!!$this->file->starred,
            'parent_id' => $this->file->parent_id,

        ];
    }

    public function get_file_size()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2, '.', ',') . '' . $units[$power];
    }
}
