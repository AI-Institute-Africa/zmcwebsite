<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\AppealCategory;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function index()
    {
        $categories = AppealCategory::all();
        return view('staff.appeals.settings', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:appeal_categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        AppealCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $request->has('is_active')
        ]);

        return back()->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, AppealCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:appeal_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $request->has('is_active')
        ]);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(AppealCategory $category)
    {
        if ($category->complaints()->count() > 0) {
            return back()->with('error', 'Cannot delete category in use by appeals.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
