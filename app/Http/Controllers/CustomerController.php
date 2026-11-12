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
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                // escape ตัวอักษรพิเศษของ LIKE
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('license_plate', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('address', 'like', $like);
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'status'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'email'         => 'nullable|email|max:255|unique:customers,email',
            'address'       => 'nullable|string',
            'license_plate' => 'nullable|string|max:50',
        ]);
        $validated['is_active'] = $request->has('is_active') ? true : false;
        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'เพิ่มข้อมูลลูกค้าสำเร็จ!');
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
        if (\App\Models\Sale::where('customer_id', $customer->id)->exists()
            || \App\Models\Quotation::where('customer_id', $customer->id)->exists()
            || \App\Models\StockDocument::where('customer_id', $customer->id)->exists()) {
            return back()->with('error', 'ลบไม่ได้ เพราะมีบิล/ใบเสนอราคา/ใบเบิกอ้างอิงลูกค้ารายนี้ (ให้ปิดใช้งานแทน)');
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'ลบข้อมูลลูกค้าสำเร็จ!');
    }
}
