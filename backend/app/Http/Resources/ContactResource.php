<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    private function maskPhone(?string $phone, Request $request): ?string
    {
        if (!$phone) return null;
        if ($request->user()?->isAdmin()) return $phone;
        $len = strlen($phone);
        if ($len <= 7) return str_repeat('*', $len);
        return substr($phone, 0, 5) . str_repeat('*', max(4, $len - 7)) . substr($phone, -2);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'phone'          => $this->maskPhone($this->phone, $request),
            'email'          => $this->email,
            'opted_in'       => $this->opted_in,
            'messages_count' => $this->messages_count ?? null,
            'last_visit'     => $this->last_visit?->toDateString(),
            'notes'          => $this->notes,
            'country'        => $this->country,
            'language'       => $this->language,
            'status'         => $this->status ?? 'active',
            'source'         => $this->source ?? 'manual',
            'created_at'     => $this->created_at->toISOString(),
            'updated_at'     => $this->updated_at->toISOString(),
            'deleted_at'     => $this->deleted_at?->toISOString(),
            'tags'           => $this->whenLoaded('tags', fn() => $this->tags->map(fn($tag) => [
                'id'    => $tag->id,
                'name'  => $tag->name,
                'color' => $tag->color ?? null,
            ])),
            'activities'    => $this->whenLoaded('activities'),
            'contact_notes' => $this->whenLoaded('notes'),
        ];
    }
}
