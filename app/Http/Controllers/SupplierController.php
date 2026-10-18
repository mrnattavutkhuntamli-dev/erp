<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use GuzzleHttp\Promise\Create;
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
            'contact_name' => 'required|string|max:255',
            'phone'=>'nullable|string|max:10',
            'email'        => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true:false;
        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'เพิ่มซัพพลายเออร์สำเร็จ!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       return view('suppliers.show', compact('supplier'));
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
        ]);
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'อัปเดตซัพพลายเออร์สำเร็จ!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'ลบซัพพลายเออร์สำเร็จ!');
    }
}
