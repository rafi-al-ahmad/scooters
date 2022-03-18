<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'meta_desc' => $this->meta_desc,
            'code' => $this->code,
            'warranty' => $this->warranty,
            'brand_id' => $this->brand_id,
            'collection_id' => $this->collection_id,
            'product_type' => $this->product_type,
            'languages' => $this->languages,
            'videos' => $this->videos,
            'status' => $this->status,
            'technical_specifications' => $this->technical_specifications,
            'main_image' => $this->getMainImage(),
            'media' => $this->getPreparedMedia(),
        ];
    }
}
