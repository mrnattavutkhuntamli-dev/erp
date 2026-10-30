<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // แสดงเฉพาะข้อมูลที่ role นั้นมีสิทธิ์เห็น (ผู้ใช้ที่ไม่มี role จะไม่เห็นข้อมูลธุรกิจ)
        $canSeeStock     = $user->can('view-products') || $user->can('view-stock');
        $canSeeSuppliers = $user->can('manage-suppliers') || $user->can('view-reports');
        $canSeeValue     = $user->can('view-reports'); // มูลค่าตามราคาทุน = ข้อมูลอ่อนไหว

        $totalProducts    = $canSeeStock ? Product::count() : 0;
        $totalSuppliers   = $canSeeSuppliers ? Supplier::count() : 0;
        $totalStockValue  = $canSeeValue ? Product::sum(DB::raw('cost * stock')) : null;
        $lowStockProducts = $canSeeStock
            ? Product::where('stock', '<', 10)->where('is_active', true)->get()
            : collect();

        return view('dashboard', compact(
            'totalProducts', 'totalSuppliers', 'totalStockValue', 'lowStockProducts',
            'canSeeStock', 'canSeeSuppliers'
        ));
    }
}
