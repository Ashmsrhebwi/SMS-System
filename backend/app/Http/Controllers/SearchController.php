<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\SmsTemplate;
use App\Models\Tag;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . $q . '%';
        $isAdmin = auth()->user()->isAdmin();

        $contacts = Contact::where(function ($query) use ($like) {
            $query->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like);
        })->limit(5)->get(['id', 'name', 'phone', 'email']);

        $campaigns = Campaign::where('name', 'like', $like)
            ->limit(5)->get(['id', 'name', 'status']);

        $templates = $isAdmin
            ? SmsTemplate::where('name', 'like', $like)->limit(5)->get(['id', 'name'])
            : collect();

        $tags = $isAdmin
            ? Tag::where('name', 'like', $like)->limit(5)->get(['id', 'name', 'color'])
            : collect();

        $segments = $isAdmin
            ? Segment::where('name', 'like', $like)->limit(5)->get(['id', 'name'])
            : collect();

        $results = [];

        if ($contacts->isNotEmpty()) {
            $results[] = [
                'group' => 'Contacts',
                'items' => $contacts->map(fn($c) => [
                    'id'    => $c->id,
                    'label' => $c->name,
                    'sub'   => $isAdmin ? $c->phone : mask_phone($c->phone ?? ''),
                    'url'   => route('contacts.show', $c->id),
                    'icon'  => 'user',
                ]),
            ];
        }

        if ($campaigns->isNotEmpty()) {
            $results[] = [
                'group' => 'Campaigns',
                'items' => $campaigns->map(fn($c) => [
                    'id'    => $c->id,
                    'label' => $c->name,
                    'sub'   => ucfirst($c->status),
                    'url'   => route('campaigns.show', $c->id),
                    'icon'  => 'megaphone',
                ]),
            ];
        }

        if ($templates->isNotEmpty()) {
            $results[] = [
                'group' => 'Templates',
                'items' => $templates->map(fn($t) => [
                    'id'    => $t->id,
                    'label' => $t->name,
                    'sub'   => 'SMS Template',
                    'url'   => route('templates.edit', $t->id),
                    'icon'  => 'document',
                ]),
            ];
        }

        if ($tags->isNotEmpty()) {
            $results[] = [
                'group' => 'Tags',
                'items' => $tags->map(fn($t) => [
                    'id'    => $t->id,
                    'label' => $t->name,
                    'sub'   => 'Tag',
                    'url'   => route('tags.edit', $t->id),
                    'icon'  => 'tag',
                    'color' => $t->color,
                ]),
            ];
        }

        if ($segments->isNotEmpty()) {
            $results[] = [
                'group' => 'Segments',
                'items' => $segments->map(fn($s) => [
                    'id'    => $s->id,
                    'label' => $s->name,
                    'sub'   => 'Segment',
                    'url'   => route('segments.edit', $s->id),
                    'icon'  => 'filter',
                ]),
            ];
        }

        return response()->json(['results' => $results]);
    }
}
