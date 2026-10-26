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
            ->orderBy('created_at', 'asc')
            ->get();

        $agingData = [];
        $totals    = [
            'current'      => 0, // 0-30 วัน
            'days_31_60'   => 0, // 31-60 วัน
            'days_60_plus' => 0, // 60+ วัน
            'total'        => 0,
        ];

        $now = now(); // ประกาศนอกลูปเพื่อประสิทธิภาพ

        foreach ($unpaidSales as $sale) {
            // คำนวณยอดค้างชำระ (ปัดเศษทศนิยม 2 ตำแหน่ง และป้องกันติดลบ)
            $dueAmount   = round(max(0, $sale->total - $sale->paid_amount), 2);
            $daysOverdue = (int) $now->diffInDays($sale->created_at);

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
                'total'      => $sale->total,
                'paid'       => $sale->paid_amount,
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
