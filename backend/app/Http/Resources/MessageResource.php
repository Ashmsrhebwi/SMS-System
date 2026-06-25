<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'id'            => $this->id,
            'status'        => $this->status,
            'error_code'    => $this->error_code,
            'error_message' => $this->error_message,
            'cost'          => $this->cost,
            'sms_segments'  => $this->sms_segments,
            'resend_count'  => $this->resend_count,
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
            'contact'       => $this->whenLoaded('contact', fn() => $this->contact ? [
                'id'    => $this->contact->id,
                'name'  => $this->contact->name,
                'phone' => $this->maskPhone($this->contact->phone, $request),
            ] : null),
            'click'         => $this->whenLoaded('click', fn() => $this->click ? [
                'click_count' => $this->click->click_count,
                'last_click'  => $this->click->updated_at?->toISOString(),
            ] : null),
        ];
    }
}
