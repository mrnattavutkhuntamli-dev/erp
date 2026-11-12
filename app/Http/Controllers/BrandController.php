<?php
namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $brands = Brand::query()
            ->withCount('products')
            ->when($search !== '', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('brands.index', compact('brands', 'search'));
    }
    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255|unique:brands,name']);
        Brand::create($validated);
        return redirect()->route('brands.index')->with('success', 'เพิ่มยี่ห้ออะไหล่สำเร็จ!');
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate(['name' => 'required|string|max:255|unique:brands,name,' . $brand->id]);
        $brand->update($validated);
        return redirect()->route('brands.index')->with('success', 'อัปเดตยี่ห้ออะไหล่สำเร็จ!');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            return redirect()->route('brands.index')->with('error', 'ไม่สามารถลบได้ เนื่องจากมีสินค้าที่ใช้ยี่ห้อนี้อยู่');
        }
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'ลบยี่ห้ออะไหล่สำเร็จ!');
    }
}
