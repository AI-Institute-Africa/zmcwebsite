<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\WebsitePagePerson;
use App\Models\WebsitePageSection;
use App\Models\WebsitePageTitle;
use App\Support\AuditTrail;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WebsitePeopleController extends Controller
{
    private const PAGES = [
        'commissioners' => 'Commissioners',
        'secretariat' => 'Secretariat',
    ];

    public function __construct()
    {
        // These routes are also protected by the PR route group. Keeping the
        // rule here prevents a future route change from exposing website
        // profiles to non-PR staff members.
        $this->middleware(['auth', 'staff.portal', 'role:pr_officer|super_admin']);
    }

    public function index(?string $page = null)
    {
        $selectedPages = $page && array_key_exists($page, self::PAGES) ? [$page => self::PAGES[$page]] : self::PAGES;
        $pageKeys = array_keys($selectedPages);

        $sections = WebsitePageSection::query()
            ->whereIn('page_key', $pageKeys)
            ->orderBy('page_key')->orderBy('sort_order')->orderBy('id')
            ->get()
            ->groupBy('page_key');

        $titles = WebsitePageTitle::query()
            ->where(function ($query) use ($pageKeys) {
                $query->whereNull('page_key')->orWhereIn('page_key', $pageKeys);
            })
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $people = WebsitePagePerson::query()
            ->with(['title', 'section'])
            ->whereIn('page_key', $pageKeys)
            ->orderBy('page_key')->orderBy('tier')->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('staff.pr.website_people', [
            'peopleByPage' => $people->groupBy('page_key'),
            'sectionsByPage' => $sections,
            'titles' => $titles,
            'pages' => $selectedPages,
            'selectedPage' => $page,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['image_path'] = $this->storeImage($request);
        $data['created_by'] = $request->user()->id;
        try {
            $person = WebsitePagePerson::create($data);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($data['image_path']);
            throw $exception;
        }

        AuditTrail::log('website_page_person_create', $person, [
            'page' => $person->page_key,
            'is_published' => $person->is_published,
        ]);

        return back()->with('success', 'Website page profile added.');
    }

    public function update(Request $request, WebsitePagePerson $person)
    {
        $data = $this->validated($request, false);
        $newImagePath = $request->hasFile('image') ? $this->storeImage($request) : null;
        $oldImagePath = $person->image_path;
        $clearImage = $request->boolean('remove_image') && !$newImagePath;

        if ($newImagePath) {
            $data['image_path'] = $newImagePath;
        } elseif ($clearImage) {
            $data['image_path'] = null;
        }

        try {
            $person->update($data);
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if (($newImagePath || $clearImage) && $oldImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        AuditTrail::log('website_page_person_update', $person, [
            'page' => $person->page_key,
            'is_published' => $person->is_published,
            'image_changed' => (bool) ($newImagePath || $clearImage),
        ]);

        return back()->with('success', 'Website page profile updated.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'page_key' => ['required', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.section_id' => ['nullable', 'integer'],
            'items.*.tier' => ['nullable', 'integer', 'min:1', 'max:255'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $sections = WebsitePageSection::where('page_key', $data['page_key'])->get()->keyBy('id');

        DB::transaction(function () use ($data, $sections) {
            foreach ($data['items'] as $item) {
                $sectionId = $item['section_id'] ?? null;
                $section = $sectionId ? $sections->get($sectionId) : null;
                $tier = $section ? $section->sort_order : ($item['tier'] ?? null);

                $values = ['sort_order' => $item['sort_order']];
                if ($section) {
                    $values['section_id'] = $section->id;
                    $values['tier'] = $tier;
                } elseif ($tier) {
                    $values['tier'] = $tier;
                }

                WebsitePagePerson::query()
                    ->where('id', $item['id'])
                    ->where('page_key', $data['page_key'])
                    ->update($values);
            }
        });

        AuditTrail::log('website_page_people_reorder', null, ['page' => $data['page_key'], 'count' => count($data['items'])]);

        return back()->with('success', self::PAGES[$data['page_key']] . ' sections and order saved.');
    }

    public function destroy(WebsitePagePerson $person)
    {
        $imagePath = $person->image_path;
        $page = $person->page_key;
        $person->delete();
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        AuditTrail::log('website_page_person_delete', null, [
            'person_id' => $person->id,
            'page' => $page,
        ]);

        return back()->with('success', 'Website page profile removed.');
    }

    // ─── Page sections (the headings shown on the public page) ───────────────

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'page_key' => ['required', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'name' => ['required', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? ((int) WebsitePageSection::where('page_key', $data['page_key'])->max('sort_order') + 1);
        $section = WebsitePageSection::create($data);

        AuditTrail::log('website_page_section_create', $section, ['page' => $section->page_key]);

        return back()->with('success', 'Section "' . $section->name . '" added.');
    }

    public function updateSection(Request $request, WebsitePageSection $section)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $oldOrder = $section->sort_order;
        $section->update($data);

        if ($oldOrder !== $section->sort_order) {
            // Keep each profile's tier in sync with its section order.
            WebsitePagePerson::where('section_id', $section->id)->update(['tier' => $section->sort_order]);
        }

        AuditTrail::log('website_page_section_update', $section, ['page' => $section->page_key]);

        return back()->with('success', 'Section updated.');
    }

    public function destroySection(WebsitePageSection $section)
    {
        $name = $section->name;
        $section->delete();

        AuditTrail::log('website_page_section_delete', null, ['section' => $name]);

        return back()->with('success', 'Section "' . $name . '" removed. Its profiles are now ungrouped.');
    }

    // ─── Reusable job titles ─────────────────────────────────────────────────

    public function storeTitle(Request $request)
    {
        $data = $request->validate([
            'page_key' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'name' => ['required', 'string', 'max:160', 'unique:website_page_titles,name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['page_key'] = $data['page_key'] ?: null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        WebsitePageTitle::create($data);

        return back()->with('success', 'Title "' . $data['name'] . '" added.');
    }

    public function updateTitle(Request $request, WebsitePageTitle $title)
    {
        $data = $request->validate([
            'page_key' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'name' => ['required', 'string', 'max:160', 'unique:website_page_titles,name,' . $title->id],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['page_key'] = $data['page_key'] ?: null;
        $data['sort_order'] = $data['sort_order'] ?? $title->sort_order;
        $title->update($data);

        return back()->with('success', 'Title updated.');
    }

    public function destroyTitle(WebsitePageTitle $title)
    {
        $name = $title->name;
        $title->delete();

        return back()->with('success', 'Title "' . $name . '" removed.');
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'page_key' => ['required', 'string', 'in:' . implode(',', array_keys(self::PAGES))],
            'section_id' => ['nullable', 'integer'],
            'title_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'position' => ['nullable', 'string', 'max:200'],
            'tier' => ['nullable', 'integer', 'min:1', 'max:255'],
            'department' => ['nullable', 'string', 'max:160'],
            'biography' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_published' => ['required', 'boolean'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        unset($data['image'], $data['remove_image']);

        if (!empty($data['section_id'])) {
            $section = WebsitePageSection::where('id', $data['section_id'])
                ->where('page_key', $data['page_key'])
                ->first();
            if (!$section) {
                abort(422, 'The selected section does not belong to this page.');
            }
            $data['tier'] = $section->sort_order;
        } else {
            $data['section_id'] = null;
            $data['tier'] = $data['tier'] ?? 3;
        }

        if (!empty($data['title_id'])) {
            $title = WebsitePageTitle::where('id', $data['title_id'])
                ->where(function ($query) use ($data) {
                    $query->whereNull('page_key')->orWhere('page_key', $data['page_key']);
                })
                ->first();
            if (!$title) {
                abort(422, 'The selected title is not available for this page.');
            }
            if (empty($data['position'])) {
                $data['position'] = $title->name;
            }
        } else {
            $data['title_id'] = null;
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = (bool) $data['is_published'];

        return $data;
    }

    private function storeImage(Request $request): string
    {
        $image = $request->file('image');
        return $image->storeAs('website/people', FileType::safeHashName($image), 'public');
    }
}
