<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            'song' => $this->song,
            'singer' => $this->singer,
            'instrument' => $this->instrument,
            'type' => $this->type,
            'level' => $this->level,
            'image' => asset('storage/' . $this->image),
            'audio' => asset('storage/' . $this->audio),
            'video' => asset('storage/' . $this->video),
            'pdf' => asset('storage/' . $this->pdf),
        ];
    }
}
