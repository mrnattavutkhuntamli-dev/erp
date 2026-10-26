<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('user')->latest('expense_date');

        if ($request->filled('month'))    $query->whereMonth('expense_date', $request->month);
        if ($request->filled('year'))     $query->whereYear('expense_date', $request->year);
        if ($request->filled('category')) $query->where('category', $request->category);

        $totalAmount = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();

        return view('expenses.index', compact('expenses', 'totalAmount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category'     => 'required|in:rent,utilities,salary,supplies,other',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01|max:9999999.99',
        ]);

        $validated['user_id'] = auth()->id();
        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'บันทึกค่าใช้จ่ายสำเร็จ!');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category'     => 'required|in:rent,utilities,salary,supplies,other',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0.01|max:9999999.99',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'อัปเดตค่าใช้จ่ายสำเร็จ!');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'ลบค่าใช้จ่ายสำเร็จ!');
    }

    // ✅ เพิ่มฟังก์ชันนี้สำหรับดึงข้อมูล Expense ผ่าน AJAX (ใช้ใน Edit Drawer)
    public function editData(Expense $expense)
    {
        return response()->json([
            'id'           => $expense->id,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'category'     => $expense->category,
            'description'  => $expense->description,
            'amount'       => $expense->amount,
        ]);
    }
}
