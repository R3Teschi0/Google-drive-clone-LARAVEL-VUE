<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            "id" => $this->id,
            "name" => $this->name,
            "path"=> $this->path,
            "parent_id"=> $this->parent_id,
            "is_folder"=> $this->is_folder ,
            "mime" => $this->mime,
            "size" => $this->get_file_size(),
            'owner' => $this->owner,
            'is_favourite' => !!$this->starred,
            "created_at" => $this->created_at->diffForHumans(),
            "updated_at" => $this->updated_at->diffForHumans(),
            "created_by" => $this->created_by,
            "updated_by" => $this->updated_by,
            "deleted_at" => $this->deleted_at,
        ];
    }

    public function get_file_size(){
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size/pow(1024, $power), 2, '.', ','). '' . $units[$power];
    }
}
