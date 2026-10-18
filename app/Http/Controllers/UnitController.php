<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::latest()->get();
        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
           'name' => 'required|string|max:255|unique:units,name',
           'short_name' => 'required|string|max:50|unique:units,short_name',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'เพิ่มหน่วยนับสำเร็จ!');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
           'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
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
