<?php
namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $units = Unit::query()
            ->withCount('products')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('units.index', compact('units', 'search'));
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:units,name',
            'short_name' => 'required|string|max:50|unique:units,short_name',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'เพิ่มหน่วยนับสำเร็จ!');
    }



    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:units,name,' . $unit->id,
            'short_name' => 'required|string|max:50|unique:units,short_name,' . $unit->id,
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'อัปเดตหน่วยนับสำเร็จ!');
    }

    public function destroy(Unit $unit)
    {
        // ป้องกันการลบหน่วยที่มีสินค้าใช้งานอยู่
        if ($unit->products()->count() > 0) {
            return redirect()->route('units.index')->with('error', 'ไม่สามารถลบได้ เนื่องจากมีสินค้าที่ใช้หน่วยนี้อยู่');
        }

        $unit->delete();
        return redirect()->route('units.index')->with('success', 'ลบหน่วยนับสำเร็จ!');
    }
}
