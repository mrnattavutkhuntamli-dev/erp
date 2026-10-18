<?php

namespace App\Http\Controllers;


use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::latest()->get();
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $validated = $request->validate([
           'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);
        $validated['slug'] = Str::slug($validated['name']) . '-' . rand(100, 999);
        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'เพิ่มหมวดหมู่สำเร็จ!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
         $validated = $request->validate([
           'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
        if ($request->name !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . rand(100, 999);
        }

        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'อัปเดตหมวดหมู่สำเร็จ!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return redirect()->route('categories.index')->with('error', 'ไม่สามารถลบได้ เนื่องจากมีสินค้าอยู่ในหมวดหมู่นี้');
        }
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'ลบหมวดหมู่สำเร็จ!');
    }
}
