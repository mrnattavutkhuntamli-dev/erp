<?php
namespace App\Http\Controllers;
use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
class ArAgingController extends Controller
{
    public function index()
    {
        $data = $this->getAgingReportData();
        return view('reports.ar-aging', $data);
    }

    public function exportPdf()
    {
        $data = $this->getAgingReportData();

        $pdf = Pdf::setOption([
            'fontDir'     => storage_path('fonts'),
            'fontCache'   => storage_path('fonts'),
            'tempDir'     => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('reports.ar-aging-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('AR-Aging-Report-' . date('Ymd') . '.pdf');
    }

    private function getAgingReportData(): array
    {
        $unpaidSales = Sale::with('customer')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('status', 'completed')
            ->orderByRaw('COALESCE(due_date, created_at) asc')
            ->get();

        $agingData = [];
        $totals    = [
            'current'      => 0, // ยังไม่เกินกำหนด - เกินกำหนด 30 วัน
            'days_31_60'   => 0, // 31-60 วัน
            'days_60_plus' => 0, // 60+ วัน
            'total'        => 0,
        ];

        $now = now(); // ประกาศนอกลูปเพื่อประสิทธิภาพ

        foreach ($unpaidSales as $sale) {
            // คำนวณยอดค้างชำระ (ปัดเศษทศนิยม 2 ตำแหน่ง และป้องกันติดลบ)
            // ใช้ยอดค้างจริงของบิล (หักของที่รับคืนและเงินที่คืนลูกค้าแล้ว) ให้ตรงกับหน้าใบรับเงิน
            $dueAmount   = $sale->outstanding_amount;
            if ($dueAmount <= 0) {
                continue;
            }
            // นับจาก "วันครบกำหนด" (due_date) — ถ้าเป็นบิลเก่าที่ไม่มี due_date ให้ใช้วันที่ขาย + เครดิตเทอมเริ่มต้น
            $dueDate     = $sale->due_date
                ?? $sale->created_at->copy()->addDays((int) config('pos.credit_days'));
            $daysOverdue = $now->startOfDay()->gt($dueDate->copy()->startOfDay())
                ? (int) $dueDate->copy()->startOfDay()->diffInDays($now->copy()->startOfDay())
                : 0; // ยังไม่ถึงกำหนด = 0 วัน

            // จัดกลุ่มตามลูกค้า
            $customerId   = $sale->customer_id ?? 0;
            $customerName = $sale->customer->name ?? 'ลูกค้าทั่วไป';

            if (! isset($agingData[$customerId])) {
                $agingData[$customerId] = [
                    'name'         => $customerName,
                    'phone'        => $sale->customer->phone ?? '-',
                    'invoices'     => [],
                    'current'      => 0,
                    'days_31_60'   => 0,
                    'days_60_plus' => 0,
                    'total'        => 0,
                ];
            }

            $agingData[$customerId]['invoices'][] = [
                'invoice_no' => $sale->invoice_no,
                'date'       => $sale->created_at->format('d/m/Y'),
                'due_date'   => $dueDate->format('d/m/Y'),
                // ยอดบิลสุทธิหลังรับคืน และเงินที่จ่ายสุทธิหลังคืนเงิน → ยอดบิล − จ่าย = ค้างชำระ
                'total'      => round((float) $sale->total - (float) $sale->returned_amount, 2),
                'paid'       => round((float) $sale->paid_amount - (float) $sale->refunded_amount, 2),
                'due'        => $dueAmount,
                'days'       => $daysOverdue,
            ];

            // แบ่งช่วงอายุหนี้
            if ($daysOverdue <= 30) {
                $agingData[$customerId]['current'] += $dueAmount;
                $totals['current']                 += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $agingData[$customerId]['days_31_60'] += $dueAmount;
                $totals['days_31_60']                 += $dueAmount;
            } else {
                $agingData[$customerId]['days_60_plus'] += $dueAmount;
                $totals['days_60_plus']                 += $dueAmount;
            }

            $agingData[$customerId]['total'] += $dueAmount;
            $totals['total']                 += $dueAmount;
        }
        return compact('agingData', 'totals');
    }
}
