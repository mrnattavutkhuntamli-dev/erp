<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ApAgingController extends Controller
{
    public function index()
    {
        // ดึง PO ที่ยังไม่ถูกยกเลิก (สถานะ pending หรือ received)
        // *ในระบบจริง A/P จะนับก็ต่อเมื่อรับของแล้ว (received) แต่ยังไม่จ่ายเงิน
        // *แต่ใน Mini ERP นี้เราจะนับ PO ที่ยัง pending และ received เป็นเจ้าหนี้ไว้ก่อน
        $unpaidPOs = PurchaseOrder::with('supplier')
            ->whereIn('status', ['pending', 'received'])
            ->orderBy('created_at', 'asc')
            ->get();

        $agingData = [];
        $totals = [
            'current'      => 0, // 0-30 วัน
            'days_31_60'   => 0, // 31-60 วัน
            'days_60_plus' => 0, // 60+ วัน
            'total'        => 0,
        ];

        foreach ($unpaidPOs as $po) {
            $dueAmount = $po->total_amount;
            $daysOverdue = (int) $po->created_at->diffInDays(now());

            $supplierId = $po->supplier_id;
            $supplierName = $po->supplier->name ?? 'ไม่ระบุซัพพลายเออร์';

            if (!isset($agingData[$supplierId])) {
                $agingData[$supplierId] = [
                    'name' => $supplierName,
                    'phone' => $po->supplier->phone ?? '-',
                    'pos' => [],
                    'current' => 0,
                    'days_31_60' => 0,
                    'days_60_plus' => 0,
                    'total' => 0,
                ];
            }

            $agingData[$supplierId]['pos'][] = [
                'po_no' => $po->po_no,
                'date' => $po->order_date->format('d/m/Y'),
                'status' => $po->status,
                'total' => $po->total_amount,
                'days' => $daysOverdue,
            ];

            if ($daysOverdue <= 30) {
                $agingData[$supplierId]['current'] += $dueAmount;
                $totals['current'] += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $agingData[$supplierId]['days_31_60'] += $dueAmount;
                $totals['days_31_60'] += $dueAmount;
            } else {
                $agingData[$supplierId]['days_60_plus'] += $dueAmount;
                $totals['days_60_plus'] += $dueAmount;
            }

            $agingData[$supplierId]['total'] += $dueAmount;
            $totals['total'] += $dueAmount;
        }

        return view('reports.ap-aging', compact('agingData', 'totals'));
    }

    public function exportPdf()
    {
        $unpaidPOs = PurchaseOrder::with('supplier')
            ->whereIn('status', ['pending', 'received'])
            ->orderBy('created_at', 'asc')
            ->get();

        $agingData = [];
        $totals = [
            'current' => 0, 'days_31_60' => 0, 'days_60_plus' => 0, 'total' => 0,
        ];

        foreach ($unpaidPOs as $po) {
            $dueAmount = $po->total_amount;
            $daysOverdue = (int) $po->created_at->diffInDays(now());
            $supplierId = $po->supplier_id;
            $supplierName = $po->supplier->name ?? 'ไม่ระบุซัพพลายเออร์';

            if (!isset($agingData[$supplierId])) {
                $agingData[$supplierId] = [
                    'name' => $supplierName,
                    'phone' => $po->supplier->phone ?? '-',
                    'pos' => [],
                    'current' => 0, 'days_31_60' => 0, 'days_60_plus' => 0, 'total' => 0,
                ];
            }

            $agingData[$supplierId]['pos'][] = [
                'po_no' => $po->po_no,
                'date' => $po->order_date->format('d/m/Y'),
                'status' => $po->status,
                'total' => $po->total_amount,
                'days' => $daysOverdue,
            ];

            if ($daysOverdue <= 30) {
                $agingData[$supplierId]['current'] += $dueAmount;
                $totals['current'] += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $agingData[$supplierId]['days_31_60'] += $dueAmount;
                $totals['days_31_60'] += $dueAmount;
            } else {
                $agingData[$supplierId]['days_60_plus'] += $dueAmount;
                $totals['days_60_plus'] += $dueAmount;
            }

            $agingData[$supplierId]['total'] += $dueAmount;
            $totals['total'] += $dueAmount;
        }

        $pdf = Pdf::setOption([
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'tempDir' => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('reports.ap-aging-pdf', compact('agingData', 'totals'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('AP-Aging-Report-' . date('Ymd') . '.pdf');
    }
}
