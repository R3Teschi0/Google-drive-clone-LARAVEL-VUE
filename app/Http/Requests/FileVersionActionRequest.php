<?php

namespace App\Http\Requests;

use App\Models\FileVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FileVersionActionRequest extends ParentIdBaseRequest
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
            'file_base_id' => 'nullable|integer',
            'all' => 'nullable|bool',
            'ids.*' => [
                Rule::exists('file_versions', 'id'),
                function ($attribute, $id, $fail) {
                    $file = FileVersion::query()
                    ->where('id', $id)
                    ->first();

                    if (blank($file)){
                        $fail('Invalid ID "' . $id . '"');
                    }
                }
            ]

        ]);
    }
}
