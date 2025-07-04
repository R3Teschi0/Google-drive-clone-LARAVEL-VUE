<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrashFilesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'all' => 'nullable|bool',
            'ids.*' => Rule::exists('file_versions', 'id')->where(function ($query) {
                $query->whereExists(function ($q){
                    $q->select(DB::raw(1))->from('files')->whereColumn('files.id','file_versions.file_id')->where('created_by', Auth::id());
                });
            }),
        ];
    }
}
