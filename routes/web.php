<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\StockDocumentController;

use App\Models\Product;
use App\Models\Supplier;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::resource('products', ProductController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('units', UnitController::class);

    Route::get('/stock-documents', [StockDocumentController::class, 'index'])->name('stock-documents.index');
    Route::get('/stock-documents/create', [StockDocumentController::class, 'create'])->name('stock-documents.create');
    Route::post('/stock-documents', [StockDocumentController::class, 'store'])->name('stock-documents.store');
    Route::get('/stock-documents/{stockDocument}', [StockDocumentController::class, 'show'])->name('stock-documents.show');
    Route::get('/stock-documents/{stockDocument}/edit', [StockDocumentController::class, 'edit'])->name('stock-documents.edit');
    Route::put('/stock-documents/{stockDocument}', [StockDocumentController::class, 'update'])->name('stock-documents.update');
    Route::delete('/stock-documents/{stockDocument}', [StockDocumentController::class, 'destroy'])->name('stock-documents.destroy');


    Route::get('/dashboard', function () {
         // ดึงข้อมูลสรุป
        $totalProducts = Product::count();
        $totalSuppliers = Supplier::count();

        // คำนวณมูลค่าสต็อกรวม (ราคาขาย x จำนวนสต็อก)
        $totalStockValue = Product::sum(DB::raw('price * stock'));

        // ดึงสินค้าที่สต็อกต่ำกว่า 10 ชิ้น (แจ้งเตือน)
        $lowStockProducts = Product::where('stock', '<', 10)->where('is_active', true)->get();

        return view('dashboard', compact('totalProducts', 'totalSuppliers', 'totalStockValue', 'lowStockProducts'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
