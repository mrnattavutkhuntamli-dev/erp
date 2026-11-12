<?php

return [
    // true = พนักงานที่ไม่ใช่ admin ต้องเปิดกะก่อนจึงขายได้ (POS และแปลงใบเสนอราคาเป็นบิล)
    // ค่าเริ่มต้นเป็น true (บังคับเปิดกะ); ถ้าจำเป็นต้องปิดการบังคับจริง ๆ ให้ตั้งใน .env:  POS_REQUIRE_OPEN_SHIFT=false
    'require_open_shift' => filter_var(env('POS_REQUIRE_OPEN_SHIFT', true), FILTER_VALIDATE_BOOLEAN),

    // เครดิตเทอม (วัน) สำหรับการขายเชื่อ ใช้คำนวณวันครบกำหนด (due_date) ของลูกหนี้
    // ตั้งใน .env:  POS_CREDIT_DAYS=30
    'credit_days' => (int) env('POS_CREDIT_DAYS', 30),

    // ส่วนลดสูงสุด (% ของยอดรวม) ที่พนักงานที่ไม่ใช่ admin ใส่ได้ในใบเสนอราคา
    // ตั้งใน .env:  POS_MAX_DISCOUNT_PERCENT=10
    'max_discount_percent' => (float) env('POS_MAX_DISCOUNT_PERCENT', 10),

    // สินค้าที่สต็อกต่ำกว่าค่านี้จะขึ้นในรายการ "สต็อกเหลือน้อย" บน Dashboard
    // ตั้งใน .env:  POS_LOW_STOCK_THRESHOLD=10
    'low_stock_threshold' => (int) env('POS_LOW_STOCK_THRESHOLD', 10),

    'shift_grace_minutes' => (int) env('POS_SHIFT_GRACE_MINUTES', 15),
];
