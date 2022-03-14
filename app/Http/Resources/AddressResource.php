<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'state' => $this->state,
            'city' => $this->city,
            'street_1' => $this->street_1,
            'street_2' => $this->street_2,
            'postal_code' => $this->postal_code,
        ];
    }
}
