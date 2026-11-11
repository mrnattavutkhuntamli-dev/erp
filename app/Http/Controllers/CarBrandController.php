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
        $validated = $request->validate(['name' => 'required|string|max:255|unique:car_brands,name']);
        CarBrand::create($validated);
        return redirect()->route('car-brands.index')->with('success', 'เพิ่มยี่ห้อรถสำเร็จ!');
    }

    public function edit(CarBrand $carBrand)
    {
        return view('car_brands.edit', compact('carBrand'));
    }

    public function update(Request $request, CarBrand $carBrand)
    {
        $validated = $request->validate(['name' => 'required|string|max:255|unique:car_brands,name,' . $carBrand->id]);
        $carBrand->update($validated);
        return redirect()->route('car-brands.index')->with('success', 'อัปเดตยี่ห้อรถสำเร็จ!');
    }

    public function destroy(CarBrand $carBrand)
    {
        // กันลบแล้วข้อมูล "อะไหล่ชิ้นนี้ใช้กับรถรุ่นไหน" หายเงียบๆ (pivot product_car_model ถูก cascade)
        $linked = \Illuminate\Support\Facades\DB::table('product_car_model')
            ->whereIn('car_model_id', $carBrand->models()->select('id'))
            ->distinct()->count('product_id');

        if ($linked > 0) {
            return redirect()->route('car-brands.index')->with(
                'error',
                "ไม่สามารถลบยี่ห้อรถนี้ได้ เนื่องจากมีสินค้า {$linked} รายการผูกกับรุ่นรถของยี่ห้อนี้อยู่ (ให้เอาออกจากสินค้าก่อน)"
            );
        }

        $carBrand->delete();
        return redirect()->route('car-brands.index')->with('success', 'ลบยี่ห้อรถและรุ่นรถที่เกี่ยวข้องสำเร็จ!');
    }

    // ฟังก์ชันสำหรับบันทึกรุ่นรถจากฟอร์มด้านล่าง
    public function storeCarModel(Request $request)
    {
        $validated = $request->validate([
            'car_brand_id' => 'required|exists:car_brands,id',
            'name'         => [
                'required', 'string', 'max:255',
                // ชื่อรุ่นซ้ำได้ข้ามยี่ห้อ แต่ห้ามซ้ำในยี่ห้อเดียวกัน
                \Illuminate\Validation\Rule::unique('car_models', 'name')
                    ->where('car_brand_id', $request->input('car_brand_id')),
            ],
            'year_start'   => 'nullable|integer|min:1900|max:2100',
            'year_end'     => 'nullable|integer|min:1900|max:2100|gte:year_start',
        ]);

        \App\Models\CarModel::create($validated);

        return redirect()->route('car-brands.index')->with('success', 'เพิ่มรุ่นรถสำเร็จ!');
    }

    // ฟังก์ชันลบรุ่นรถ
public function destroyCarModel($id)
{
    $model = \App\Models\CarModel::findOrFail($id);

    $linked = \Illuminate\Support\Facades\DB::table('product_car_model')
        ->where('car_model_id', $model->id)->count();
    if ($linked > 0) {
        return redirect()->route('car-brands.index')->with(
            'error',
            "ไม่สามารถลบรุ่นรถนี้ได้ เนื่องจากมีสินค้า {$linked} รายการผูกอยู่ (ให้เอาออกจากสินค้าก่อน)"
        );
    }

    $model->delete();
    return redirect()->route('car-brands.index')->with('success', 'ลบรุ่นรถสำเร็จ!');
}

// ฟังก์ชันอัปเดตรุ่นรถ
public function updateCarModel(Request $request, $id)
{
    $model = \App\Models\CarModel::findOrFail($id);

    $validated = $request->validate([
        'name' => [
            'required', 'string', 'max:255',
            \Illuminate\Validation\Rule::unique('car_models', 'name')
                ->where('car_brand_id', $model->car_brand_id)
                ->ignore($model->id),
        ],
        'year_start' => 'nullable|integer|min:1900|max:2100',
        'year_end' => 'nullable|integer|min:1900|max:2100|gte:year_start',
    ]);

    $model->update($validated);

    return redirect()->route('car-brands.index')->with('success', 'อัปเดตรุ่นรถสำเร็จ!');
}
}
