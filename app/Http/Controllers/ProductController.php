<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * หน้าแสดงรายการสินค้า
     */
    public function index()
    {
        // ดึงรายการสินค้าทั้งหมด พร้อมข้อมูลหมวดหมู่และรูปหน้าปกสินค้า
        $products = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('product_images', function ($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                    ->where('product_images.is_primary', '=', true);
            })
        // เลือกคอลัมน์ที่ต้องการ
            ->select(
                'products.*',
                'categories.name as category_name',
                'suppliers.name as supplier_name',
                'units.name as unit_name',
                'brands.name as brand_name',
                'product_images.image_path as primary_image_path'
            )
        // เรียงลำดับและดึงข้อมูล
            ->latest('products.created_at')
            ->paginate(10);

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // ดึงข้อมูลมาเตรียมไว้สำหรับทำ drop down
        $categories = Category::all();
        $suppliers  = Supplier::all();
        $units      = Unit::all();
        $brands     = \App\Models\Brand::all();                   // ดึงยี่ห้ออะไหล่
        $carModels  = \App\Models\CarModel::with('brand')->get(); // ดึงรุ่นรถ (พร้อมยี่ห้อรถ)
        return view('products.create', compact('categories', 'suppliers', 'units', 'brands', 'carModels'));

    }

    /**
     * บันทึกข้อมูลสินค้าใหม่ลงฐานข้อมูล
     */
    public function store(Request $request)
    {
        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category_id'  => 'nullable|exists:categories,id',
            'supplier_id'  => 'nullable|exists:suppliers,id',
            'unit_id'      => 'nullable|exists:units,id',
            'brand_id'     => 'nullable|exists:brands,id',
            'cost'         => 'required|numeric|min:0',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'description'  => 'nullable|string',
            'car_models'   => 'nullable|array',       // รับ array ของรุ่นรถ
            'car_models.*' => 'exists:car_models,id', // ตรวจสอบว่า id มีจริง
            'is_active'    => 'boolean',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // กำหนดค่า default สำหรับ Checkbox (ถ้าไม่ติ๊กจะไม่มีค่าส่งมา ให้เซ็ตเป็น false)
        $validated['is_active'] = $request->has('is_active') ? true : false;

        return DB::transaction(function () use ($request, $validated) {
            $validated['sku'] = Product::generateSku();
            // บันทึกข้อมูลสินค้าลงตาราง products ก่อน เพื่อให้ได้ $product->id และ $product->sku มาใช้
            $product = Product::create($validated);

            if ($request->has('car_models')) {
                $product->carModels()->sync($request->car_models);
            }

            // จัดการอัปโหลดและเปลี่ยนชื่อรูปภาพ (ถ้ามี)
            if ($request->hasFile('images')) {
                $folder = 'products';
                // ตรวจสอบโฟลเดอร์ว่ามีหรือไม่
                if (! Storage::disk('public')->exists($folder)) {
                    // สร้างโฟลเดอร์โดยใช้ชื่อ products
                    Storage::disk('public')->makeDirectory($folder);
                }

                foreach ($request->file('images') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    $filename  = Str::slug($product->sku) . '-' . ($index + 1) . '-' . time() . '.' . $extension;
                    // บันทึกไฟล์ด้วยชื่อใหม่ลงในโฟลเดอร์ที่กำหนด
                    $path = $file->storeAs($folder, $filename, 'public');

                    //บันทึกข้อมูลรูปลงตาราง product_images
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0, // รูปแรกเป็นหน้าปก
                    ]);
                }
            }
            return redirect()->route('products.index')->with('success', 'เพิ่มข้อมูลสินค้าและบันทึกรูปภาพสำเร็จ!');
        });

    }

    /**
     *  แสดงรายละเอียดสินค้า (อาจจะยังไม่ต้องใช้ตอนนี้)
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     *  แสดงฟอร์มแก้ไขสินค้า
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        $suppliers  = Supplier::all();
        $units      = Unit::all();
        $brands     = \App\Models\Brand::all();
        $carModels  = \App\Models\CarModel::with('brand')->get();

        // ดึง ID ของรุ่นรถที่สินค้านี้รองรับอยู่แล้ว ออกมาเป็น Array
        $selectedCarModels = $product->carModels->pluck('id')->toArray();
        return view('products.edit', compact(
            'product', 'categories', 'suppliers', 'units', 'brands', 'carModels', 'selectedCarModels'
        ));
    }

    /**
     *   อัปเดตข้อมูลสินค้า
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            // ตรวจสอบ SKU ห้ามซ้ำกับของคนอื่น แต่ยอมให้ซ้ำกับตัวเองได้
            'sku'              => 'required|string|unique:products,sku,' . $product->id,
            'category_id'      => 'nullable|exists:categories,id',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'unit_id'          => 'nullable|exists:units,id',
            'brand_id'         => 'nullable|exists:brands,id',
            'cost'             => 'required|numeric|min:0',
            'price'            => 'required|numeric|min:0',
            'stock'            => 'required|integer|min:0',
            'description'      => 'nullable|string',
            'car_models'       => 'nullable|array',
            'car_models.*'     => 'exists:car_models,id',
            'images.*'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images'    => 'nullable|array',
            'primary_image_id' => 'nullable|exists:product_images,id',
        ]);
        // จัดการค่า Checkbox
        $validated['is_active'] = $request->has('is_active') ? true : false;
        return DB::transaction(function () use ($request, $product, $validated) {
            $product->update($validated);
             // อัปเดตความสัมพันธ์รุ่นรถ (sync จะลบของเก่าที่ไม่ถูกเลือกออกและเพิ่มของใหม่ให้อัตโนมัติ)
            if ($request->has('car_models')) {
                $product->carModels()->sync($request->car_models);
            } else {
                $product->carModels()->sync([]); // ถ้าไม่ติ๊กเลย ให้ลบความสัมพันธ์ทั้งหมด
            }

            // จัดการลบรูปภาพที่ผู้ใช้เลือกกดลบ (ถ้ามีส่งมา)
            if ($request->filled('delete_images')) {
                $imagesToDelete = $product->images()->whereIn('id', $request->delete_images)->get();
                foreach ($imagesToDelete as $img) {
                    // ลบไฟล์รูปจริงออกจากโฟลเดอร์ storage/app/public
                    if (Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                    // ลบ record จากตาราง product_images
                    $img->delete();
                }
            }
            // จัดการอัปโหลดรูปภาพเพิ่มใหม่ (ถ้ามี)
            if ($request->hasFile('images')) {
                $folder = 'products';
                if (! Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }
                // เช็คว่าปัจจุบันมีรูปภาพเหลืออยู่กี่รูป เพื่อรันลำดับ index ต่อ
                $existingImagesCount = $product->images()->count();

                foreach ($request->file('images') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    // ตั้งชื่อไฟล์: [SKU]-[ลำดับ]-[เวลา].ext
                    $filename = Str::slug($product->sku) . '-' . ($existingImagesCount + $index + 1) . '-' . time() . '.' . $extension;

                    $path = $file->storeAs($folder, $filename, 'public');

                    // บันทึกรูปลงตาราง product_images
                    // ถ้ายังไม่มีรูปภาพเหลืออยู่เลย ให้รูปแรกที่อัปโหลดใหม่นี้เป็น primary ทันที
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => ($existingImagesCount === 0 && $index === 0),
                    ]);
                }
            }
            // จัดการเปลี่ยนรูปหน้าปก (Primary Image) ถ้ามีการเลือกเปลี่ยน
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            if ($request->filled('primary_image_id')) {
                // ปรับรูปทั้งหมดของสินค้านี้ให้ไม่เป็น primary ก่อน
                $product->images()->update(['is_primary' => false]);
                // ตั้งรูปภาพที่เลือกให้เป็น primary
                $product->images()->where('id', $request->primary_image_id)->update(['is_primary' => true]);
            } else {
                // ป้องกันกรณีที่รูปที่เป็น primary ถูกลบไป ให้ตั้งรูปแรกที่เหลืออยู่เป็น primary อัตโนมัติ
                $firstImage = $product->images()->first();
                if (! $product->images()->where('is_primary', true)->exists() && $firstImage) {
                    $firstImage->update(['is_primary' => true]);
                }
            }
            return redirect()->route('products.index')->with('success', 'แก้ไขข้อมูลสินค้าสำเร็จ!');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // ลบไฟล์รูปภาพออกจาก Storage ก่อน
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        // ลบข้อมูลในฐานข้อมูล (รูปภาพจะถูกลบตามเพราะ cascadeOnDelete)
        $product->delete();

        return redirect()->route('products.index')->with('success', 'ลบสินค้าสำเร็จ!');
    }
}
