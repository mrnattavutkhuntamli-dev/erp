<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
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

        // กะที่เปิดอยู่ (ให้เลือกว่า "จ่ายจากลิ้นชักของกะไหน")
        $openShifts = CashierShift::with('user')->where('status', 'open')->orderBy('opened_at')->get();

        return view('expenses.index', compact('expenses', 'totalAmount', 'openShifts'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $validated['user_id'] = auth()->id();
        // ติ๊ก "จ่ายจากเงินในลิ้นชัก" → ผูกกับกะที่เปิดอยู่ แล้วระบบจะหักจาก "เงินที่ควรมีในลิ้นชัก" ตอนปิดกะ
        $validated['from_cash_drawer'] = $request->boolean('from_cash_drawer');

        $shift = null;
        if ($validated['from_cash_drawer']) {
            [$shift, $error] = $this->resolveDrawerShift($validated['cashier_shift_id'] ?? null);
            if ($error) {
                return back()->with('error', $error)->withInput();
            }
        }
        $validated['cashier_shift_id'] = $shift?->id;

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'บันทึกค่าใช้จ่ายสำเร็จ!');
    }

    public function update(Request $request, Expense $expense)
    {
        if ($error = $this->lockedByClosedShift($expense)) {
            return back()->with('error', $error);
        }

        $validated = $this->validated($request);

        $validated['from_cash_drawer'] = $request->boolean('from_cash_drawer');

        $shift = null;
        if ($validated['from_cash_drawer']) {
            [$shift, $error] = $this->resolveDrawerShift($validated['cashier_shift_id'] ?? null, $expense);
            if ($error) {
                return back()->with('error', $error)->withInput();
            }
        }
        $validated['cashier_shift_id'] = $shift?->id;

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'อัปเดตค่าใช้จ่ายสำเร็จ!');
    }

    public function destroy(Expense $expense)
    {
        if ($error = $this->lockedByClosedShift($expense)) {
            return back()->with('error', $error);
        }

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'ลบค่าใช้จ่ายสำเร็จ!');
    }

    // ✅ เพิ่มฟังก์ชันนี้สำหรับดึงข้อมูล Expense ผ่าน AJAX (ใช้ใน Edit Drawer)
    public function editData(Expense $expense)
    {
        return response()->json([
            'id'               => $expense->id,
            'expense_date'     => $expense->expense_date->format('Y-m-d'),
            'category'         => $expense->category,
            'description'      => $expense->description,
            'amount'           => $expense->amount,
            'from_cash_drawer' => (bool) $expense->from_cash_drawer,
            'cashier_shift_id' => $expense->cashier_shift_id,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date'     => 'required|date|before_or_equal:today',
            'category'         => 'required|in:rent,utilities,salary,supplies,other',
            'description'      => 'required|string|max:255',
            'amount'           => 'required|numeric|min:0.01|max:9999999.99|decimal:0,2',
            'cashier_shift_id' => 'nullable|integer',
        ]);
    }

    /**
     * หากะที่จะหักเงินสด: ใช้กะที่เลือก → ไม่เลือกให้ใช้กะที่เปิดอยู่ของตัวเอง → ถ้ามีกะเปิดอยู่กะเดียวใช้กะนั้น
     * คืน [กะ, ข้อความ error]
     */
    private function resolveDrawerShift($requestedId, ?Expense $existing = null): array
    {
        if ($requestedId) {
            $shift = CashierShift::where('id', $requestedId)->where('status', 'open')->first();

            return $shift
                ? [$shift, null]
                : [null, 'กะที่เลือกไม่ได้เปิดอยู่ (ปิดไปแล้วหรือไม่มีอยู่จริง)'];
        }

        // แก้ไขรายการเดิมที่ผูกกะที่ยังเปิดอยู่ → คงกะเดิม
        if ($existing?->cashier_shift_id) {
            $shift = CashierShift::where('id', $existing->cashier_shift_id)->where('status', 'open')->first();
            if ($shift) {
                return [$shift, null];
            }
        }

        $own = CashierShift::currentFor(auth()->user());
        if ($own) {
            return [$own, null];
        }

        $open = CashierShift::where('status', 'open')->get();
        if ($open->count() === 1) {
            return [$open->first(), null];
        }

        return [null, $open->isEmpty()
            ? 'ไม่มีกะที่เปิดอยู่ จึงไม่สามารถบันทึกเป็นค่าใช้จ่ายจากลิ้นชักได้ (เอาเครื่องหมายออก หรือให้แคชเชียร์เปิดกะก่อน)'
            : 'มีหลายกะเปิดอยู่ กรุณาเลือกกะที่จ่ายเงินจากลิ้นชัก'];
    }

    /** ค่าใช้จ่ายจากลิ้นชักของกะที่ปิดแล้ว แก้/ลบไม่ได้ (ยอดปิดกะถูกบันทึกไปแล้ว) */
    private function lockedByClosedShift(Expense $expense): ?string
    {
        if (! $expense->from_cash_drawer) {
            return null;
        }

        $shift = $expense->cashier_shift_id
            ? CashierShift::find($expense->cashier_shift_id)
            : CashierShift::closedCovering((int) $expense->user_id, $expense->created_at);

        return ($shift && $shift->isClosed())
            ? 'ไม่สามารถแก้ไข/ลบได้ เพราะเป็นค่าใช้จ่ายจากลิ้นชักของกะ#' . $shift->id . ' ที่ปิดไปแล้ว'
            : null;
    }
}
