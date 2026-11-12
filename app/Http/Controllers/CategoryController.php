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
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $categories = Category::query()
            ->withCount('products')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories', 'search'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'เพิ่มหมวดหมู่สำเร็จ!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
        if ($request->name !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);
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

    /**
     * สร้าง slug ที่ไม่ซ้ำ
     * - ชื่อภาษาไทยล้วน Str::slug() จะได้ค่าว่าง จึงใช้คำว่า 'category' แทน
     * - ต่อท้ายด้วยตัวอักษรสุ่ม 6 ตัว แล้ววนเช็กจนกว่าจะไม่ซ้ำ (เดิมสุ่มเลข 100-999 มีแค่ 900 ค่า ทำให้ชนกันและ error 500)
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';

        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
        } while (
            Category::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        );

        return $slug;
    }
}
