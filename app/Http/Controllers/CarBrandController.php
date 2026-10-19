<?php
namespace App\Http\Controllers;

use App\Models\CarBrand;
use Illuminate\Http\Request;

class CarBrandController extends Controller
{
    public function index()
    {
        // โหลดรุ่นรถมาด้วยเพื่อแสดงในหน้าเดียว
        $carBrands = CarBrand::with('models')->latest()->get();
        return view('car_brands.index', compact('carBrands'));
    }

    public function create()
    {
        return view('car_brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        CarBrand::create($validated);
        return redirect()->route('car-brands.index')->with('success', 'เพิ่มยี่ห้อรถสำเร็จ!');
    }

    public function edit(CarBrand $carBrand)
    {
        return view('car_brands.edit', compact('carBrand'));
    }

    public function update(Request $request, CarBrand $carBrand)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $carBrand->update($validated);
        return redirect()->route('car-brands.index')->with('success', 'อัปเดตยี่ห้อรถสำเร็จ!');
    }

    public function destroy(CarBrand $carBrand)
    {
        $carBrand->delete();
        return redirect()->route('car-brands.index')->with('success', 'ลบยี่ห้อรถและรุ่นรถที่เกี่ยวข้องสำเร็จ!');
    }

    // ฟังก์ชันสำหรับบันทึกรุ่นรถจากฟอร์มด้านล่าง
    public function storeCarModel(Request $request)
    {
        $validated = $request->validate([
            'car_brand_id' => 'required|exists:car_brands,id',
            'name'         => 'required|string|max:255',
            'year_start'   => 'nullable|integer',
            'year_end'     => 'nullable|integer',
        ]);

        \App\Models\CarModel::create($validated);

        return redirect()->route('car-brands.index')->with('success', 'เพิ่มรุ่นรถสำเร็จ!');
    }

    // ฟังก์ชันลบรุ่นรถ
public function destroyCarModel($id)
{
    $model = \App\Models\CarModel::findOrFail($id);
    $model->delete();
    return redirect()->route('car-brands.index')->with('success', 'ลบรุ่นรถสำเร็จ!');
}

// ฟังก์ชันอัปเดตรุ่นรถ
public function updateCarModel(Request $request, $id)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'year_start' => 'nullable|integer',
        'year_end' => 'nullable|integer',
    ]);

    $model = \App\Models\CarModel::findOrFail($id);
    $model->update($validated);

    return redirect()->route('car-brands.index')->with('success', 'อัปเดตรุ่นรถสำเร็จ!');
}
}
