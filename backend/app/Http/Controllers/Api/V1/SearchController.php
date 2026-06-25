<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SmsTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = mb_substr(trim($request->get('q', '')), 0, 100);

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $contacts = Contact::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
        })->limit(5)->get(['id', 'name', 'phone']);

        $campaigns = Campaign::where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'status']);

        $templates = SmsTemplate::where('name', 'like', "%{$q}%")
            ->limit(3)
            ->get(['id', 'name']);

        $results = [];

        foreach ($contacts as $c) {
            $results[] = ['type' => 'contact',  'id' => $c->id, 'label' => $c->name, 'sub' => $c->phone,   'href' => "/contacts/{$c->id}"];
        }
        foreach ($campaigns as $c) {
            $results[] = ['type' => 'campaign', 'id' => $c->id, 'label' => $c->name, 'sub' => $c->status,  'href' => "/campaigns/{$c->id}"];
        }
        foreach ($templates as $t) {
            $results[] = ['type' => 'template', 'id' => $t->id, 'label' => $t->name, 'sub' => 'Template',  'href' => '/templates'];
        }

        return response()->json(['results' => $results]);
    }
}
