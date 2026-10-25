<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        // 1. ดึงข้อมูลบริษัท
        $companySettings = [
            'company_name'    => Setting::get('company_name', 'AutoPart ERP'),
            'company_address' => Setting::get('company_address', '123 ถนนสุขุมวิท กรุงเทพฯ'),
            'company_phone'   => Setting::get('company_phone', '02-123-4567'),
            'company_tax_id'  => Setting::get('company_tax_id', '1234567890123'),
            'company_logo'    => Setting::get('company_logo'),
        ];

        // 2. ดึง Settings อื่นๆ (พร้อมสถานะ is_active)
        $companyKeys = array_keys($companySettings);
        $otherSettings = Setting::whereNotIn('key', $companyKeys)->orderBy('key')->get();

        return view('settings.index', compact('companySettings', 'otherSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name'    => 'required|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone'   => 'nullable|string|max:50',
            'company_tax_id'  => 'nullable|string|max:20',
            'company_logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        foreach ($validated as $key => $value) {
            if ($key !== 'company_logo') {
                Setting::set($key, $value);
            }
        }

        if ($request->hasFile('company_logo')) {
            $oldLogo = Setting::get('company_logo');
            if ($oldLogo && Storage::disk('public')->exists('logo/' . $oldLogo)) {
                Storage::disk('public')->delete('logo/' . $oldLogo);
            }

            $file = $request->file('company_logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('logo', $filename, 'public');
            Setting::set('company_logo', $filename);
        }

        return redirect()->route('settings.index')->with('success', 'บันทึกข้อมูลบริษัทสำเร็จ!');
    }

    // ✅ เพิ่ม Setting ใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'   => 'required|string|max:100|unique:settings,key|regex:/^[a-z0-9_]+$/',
            'value' => 'nullable|string',
        ]);

        Setting::create([
            'key' => $validated['key'],
            'value' => $validated['value'],
            'is_active' => true,
        ]);
        cache()->forget('setting.' . $validated['key']);

        return redirect()->route('settings.index')->with('success', 'เพิ่มการตั้งค่าสำเร็จ!');
    }

    // ✅ อัปเดต Setting (เปลี่ยน Value)
    public function updateCustom(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'nullable|string',
        ]);

        $setting = Setting::where('key', $key)->firstOrFail();
        $setting->update(['value' => $validated['value']]);
        cache()->forget('setting.' . $key);

        return redirect()->route('settings.index')->with('success', 'อัปเดตการตั้งค่าสำเร็จ!');
    }

    // ✅ สลับสถานะ เปิด/ปิด
    public function toggleActive($key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();
        $setting->update(['is_active' => !$setting->is_active]);
        cache()->forget('setting.' . $key);

        return redirect()->route('settings.index')->with('success', 'เปลี่ยนสถานะการตั้งค่าสำเร็จ!');
    }

    // ✅ ลบ Setting
    public function destroy($key)
    {
        $protectedKeys = ['company_name', 'company_address', 'company_phone', 'company_tax_id', 'company_logo'];
        if (in_array($key, $protectedKeys)) {
            return redirect()->route('settings.index')->with('error', 'ไม่สามารถลบข้อมูลบริษัทได้');
        }

        Setting::where('key', $key)->delete();
        cache()->forget('setting.' . $key);

        return redirect()->route('settings.index')->with('success', 'ลบการตั้งค่าสำเร็จ!');
    }
}
