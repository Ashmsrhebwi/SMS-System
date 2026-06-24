<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'message_body'     => $this->message_body,
            'status'           => $this->status,
            'scheduled_at'     => $this->scheduled_at?->toISOString(),
            'total_recipients' => $this->total_recipients,
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
            'segment'          => $this->whenLoaded('segment', fn() => [
                'id'   => $this->segment->id,
                'name' => $this->segment->name,
            ]),
            'creator'          => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
        ];
    }
}
