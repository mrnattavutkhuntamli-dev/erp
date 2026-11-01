<?php
namespace App\Http\Controllers;

use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class ShiftScheduleController extends Controller
{
    public function index()
    {
        // Table View ยังใช้ paginate เหมือนเดิม
        $schedules = ShiftSchedule::with('user')->latest('shift_date')->paginate(20);

        // Dropdown พนักงาน
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['cashier', 'stock', 'delivery']);
        })->where('is_active', true)->orderBy('name')->get();

        return view('shift-schedules.index', compact('schedules', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => ['required', \Illuminate\Validation\Rule::exists('users', 'id')->where('is_active', true)],
            'shift_date' => 'required|date',
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time'   => ['required', 'date_format:H:i,H:i:s', 'different:start_time'],
            'note'       => 'nullable|string|max:500',
        ]);
        $conflict = ShiftSchedule::overlaps(
            (int) $validated['user_id'], $validated['shift_date'], $validated['start_time'], $validated['end_time']
        );

        if ($conflict) {
            return back()->withErrors([
                'shift_date' => 'พนักงานคนนี้มีกะในเวลาที่ทับซ้อนอยู่แล้ว',
            ])->withInput();
        }

        ShiftSchedule::create($validated);

        return redirect()->route('shift-schedules.index')->with('success', 'สร้างตารางกะสำเร็จ!');
    }

    public function update(Request $request, ShiftSchedule $shiftSchedule)
    {
        $validated = $request->validate([
            'user_id'    => ['required', \Illuminate\Validation\Rule::exists('users', 'id')->where('is_active', true)],
            'shift_date' => 'required|date',
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time'   => ['required', 'date_format:H:i,H:i:s', 'different:start_time'],
            'note'       => 'nullable|string|max:500',
        ]);
        // ✅ ตรวจสอบกะซ้อน (ยกเว้นตัวเอง)
        $conflict = ShiftSchedule::overlaps(
            (int) $validated['user_id'], $validated['shift_date'], $validated['start_time'], $validated['end_time'], $shiftSchedule->id
        );

        if ($conflict) {
            return back()->withErrors([
                'shift_date' => 'พนักงานคนนี้มีกะในเวลาที่ทับซ้อนอยู่แล้ว',
            ])->withInput();
        }
        $shiftSchedule->update($validated);

        return redirect()->route('shift-schedules.index')->with('success', 'อัปเดตตารางกะสำเร็จ!');
    }

    public function destroy(ShiftSchedule $shiftSchedule)
    {
        $shiftSchedule->delete();
        return redirect()->route('shift-schedules.index')->with('success', 'ลบตารางกะสำเร็จ!');
    }

    // ✅ API สำหรับดึงข้อมูลผ่าน AJAX (ใช้ใน Edit Drawer)
    public function editData(ShiftSchedule $shiftSchedule)
    {
        return response()->json([
            'id'         => $shiftSchedule->id,
            'user_id'    => $shiftSchedule->user_id,
            'user_name'  => $shiftSchedule->user->name ?? ('ผู้ใช้ #' . $shiftSchedule->user_id), // ✅ เพิ่ม
            'shift_date' => $shiftSchedule->shift_date->format('Y-m-d'),
            'start_time' => $shiftSchedule->start_time,
            'end_time'   => $shiftSchedule->end_time,
            'note'       => $shiftSchedule->note,
        ]);
    }

    // ฟังก์ชันที่ไม่ได้ใช้ (บังคับตาม Resource)
    public function create()
    {return redirect()->route('shift-schedules.index');}
    public function show(ShiftSchedule $shiftSchedule)
    {return back();}
    public function edit(ShiftSchedule $shiftSchedule)
    {return back();}

    public function getEvents(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $events = ShiftSchedule::with('user')
            ->whereBetween('shift_date', [
                \Carbon\Carbon::parse($start)->format('Y-m-d'),
                \Carbon\Carbon::parse($end)->format('Y-m-d'),
            ])
            ->get()
            ->map(function ($schedule) {
                return [
                    'id'    => $schedule->id,
                    'title' => $schedule->user->name . ' (' .
                    \Carbon\Carbon::parse($schedule->start_time)->format('H:i') . '-' .
                    \Carbon\Carbon::parse($schedule->end_time)->format('H:i') . ')',
                    'start' => $schedule->shift_date->format('Y-m-d'),
                    'color' => $schedule->note === 'กะเช้า' ? '#10b981' : ($schedule->note === 'กะบ่าย' ? '#f59e0b' : '#3b82f6'),
                ];
            });

        return response()->json($events);
    }
}
