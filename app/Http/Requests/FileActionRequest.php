<?php

namespace App\Http\Requests;

use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FileActionRequest extends ParentIdBaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('all')) {
            //"true" => true, "false" => false, "1" => true, "0" => false
            $this->merge([
                'all' => filter_var($this->input('all'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'all' => 'nullable|bool',
            'ids.*' => [
                Rule::exists('files', 'id'),
                function ($attribute, $id, $fail) {
                    $file = File::query()
                        //->leftJoin('file_shares', 'file_shares.file_id', 'files.id')
                        ->where('id', $id)
                        ->where(function ($query) {
                            /** @var $query \Illuminate\Database\Query\Builder */
                            //$query->where('files.created_by', Auth::id())
                            //->orWhere('file_shares.user_id', Auth::id());
                        })
                        ->first();

                    if (blank($file)) {
                        $fail('Invalid ID "' . $id . '"');
                    }
                }
            ]
        ]);
    }
}
