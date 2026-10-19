<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::latest()->paginate(15);
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:255|unique:customer,email',
            'address'       => 'nullable|string',
            'license_plate' => 'nullable|string|max:50',
        ]);
        $validated['is_active'] = $request->has('is_active') ? true : false;
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'เพิ่มข้อมูลลูกค้าสำเร็จ!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'email'         => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'address'       => 'nullable|string',
            'license_plate' => 'nullable|string|max:50',
        ]);
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'อัปเดตข้อมูลลูกค้าสำเร็จ!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'ลบข้อมูลลูกค้าสำเร็จ!');
    }
}
