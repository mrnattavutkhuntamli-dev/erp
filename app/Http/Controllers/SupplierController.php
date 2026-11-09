<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::latest()->get();
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('suppliers.create');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:suppliers,name',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string',
            'credit_term_days' => 'nullable|integer|min:0|max:365',
        ]);

        $validated['credit_term_days'] = (int) ($validated['credit_term_days'] ?? 0);
        $validated['is_active'] = $request->has('is_active') ? true:false;
        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'เพิ่มซัพพลายเออร์สำเร็จ!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  Supplier $supplier)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string',
            'credit_term_days' => 'nullable|integer|min:0|max:365',
        ]);
        $validated['credit_term_days'] = (int) ($validated['credit_term_days'] ?? 0);
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'อัปเดตซัพพลายเออร์สำเร็จ!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'ลบไม่ได้ เพราะมีใบสั่งซื้ออ้างอิงอยู่ (แนะนำให้ปิดใช้งานแทน)');
        }

        // ใบรับเข้า/สินค้าอ้างซัพพลายเออร์แบบ nullOnDelete: ลบแล้วชื่อในประวัติจะหายเงียบ ๆ จึงต้องกันไว้
        if (\Illuminate\Support\Facades\DB::table('stock_documents')->where('supplier_id', $supplier->id)->exists()) {
            return back()->with('error', 'ลบไม่ได้ เพราะมีใบรับเข้า/ใบเบิกสินค้าอ้างอิงอยู่ (แนะนำให้ปิดใช้งานแทน)');
        }

        if (\Illuminate\Support\Facades\DB::table('products')->where('supplier_id', $supplier->id)->exists()) {
            return back()->with('error', 'ลบไม่ได้ เพราะมีสินค้าผูกกับซัพพลายเออร์นี้อยู่ (แนะนำให้ปิดใช้งานแทน)');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'ลบซัพพลายเออร์สำเร็จ!');
    }
}
