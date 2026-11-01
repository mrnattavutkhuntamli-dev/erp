<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class ApAgingController extends Controller
{
    public function index()
    {
        return view('reports.ap-aging', $this->getAgingReportData());
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

        $pdf->loadView('reports.ap-aging-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('AP-Aging-Report-' . date('Ymd') . '.pdf');
    }

    /**
     * เจ้าหนี้การค้า (A/P) = PO ที่ "รับของแล้ว" และ "ยังจ่ายเงินไม่ครบ" เท่านั้น
     *  - PO ที่ยังไม่รับของ (pending) ยังไม่ใช่หนี้ จึงไม่นับ
     *  - ยอดที่นับคือยอดคงเหลือ (total_amount - paid_amount) บันทึกการจ่ายได้ที่หน้า PO
     *  - อายุหนี้นับจาก "วันครบกำหนด" = วันรับของ (received_at; ถ้าไม่มีใช้ order_date) + เครดิตเทอมของซัพพลายเออร์
     *    (สอดคล้องกับ A/R ที่นับจาก due_date) ยังไม่ถึงกำหนด = 0 วัน
     */
    private function getAgingReportData(): array
    {
        $unpaidPOs = PurchaseOrder::with('supplier')
            ->where('status', 'received')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->orderBy('order_date', 'asc')
            ->get();

        $agingData = [];
        $totals    = ['current' => 0, 'days_31_60' => 0, 'days_60_plus' => 0, 'total' => 0];
        $today     = now()->startOfDay();

        foreach ($unpaidPOs as $po) {
            $dueAmount   = $po->remaining_amount;
            $baseDate    = ($po->received_at ?? $po->order_date)->copy()->startOfDay();
            $dueDate     = $baseDate->copy()->addDays((int) ($po->supplier->credit_term_days ?? 0));
            $daysOverdue = $today->gt($dueDate) ? (int) $dueDate->diffInDays($today) : 0;

            $supplierId   = $po->supplier_id;
            $supplierName = $po->supplier->name ?? 'ไม่ระบุซัพพลายเออร์';

            if (! isset($agingData[$supplierId])) {
                $agingData[$supplierId] = [
                    'name'         => $supplierName,
                    'phone'        => $po->supplier->phone ?? '-',
                    'pos'          => [],
                    'current'      => 0,
                    'days_31_60'   => 0,
                    'days_60_plus' => 0,
                    'total'        => 0,
                ];
            }

            $agingData[$supplierId]['pos'][] = [
                'id'     => $po->id,
                'po_no'  => $po->po_no,
                'date'   => $po->order_date->format('d/m/Y'),
                'due_date' => $dueDate->format('d/m/Y'),
                'status' => $po->status,
                'total'  => $dueAmount, // ยอดคงเหลือค้างจ่าย
                'days'   => $daysOverdue,
            ];

            if ($daysOverdue <= 30) {
                $bucket = 'current';
            } elseif ($daysOverdue <= 60) {
                $bucket = 'days_31_60';
            } else {
                $bucket = 'days_60_plus';
            }

            $agingData[$supplierId][$bucket] += $dueAmount;
            $totals[$bucket]                 += $dueAmount;
            $agingData[$supplierId]['total'] += $dueAmount;
            $totals['total']                 += $dueAmount;
        }

        return compact('agingData', 'totals');
    }
}
