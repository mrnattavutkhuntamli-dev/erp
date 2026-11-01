<?php

return [
    // true = พนักงานที่ไม่ใช่ admin ต้องเปิดกะก่อนจึงขายได้ (POS และแปลงใบเสนอราคาเป็นบิล)
    // ตั้งใน .env:  POS_REQUIRE_OPEN_SHIFT=true
    'require_open_shift' => env('POS_REQUIRE_OPEN_SHIFT', false),

    // เครดิตเทอม (วัน) สำหรับการขายเชื่อ ใช้คำนวณวันครบกำหนด (due_date) ของลูกหนี้
    // ตั้งใน .env:  POS_CREDIT_DAYS=30
    'credit_days' => (int) env('POS_CREDIT_DAYS', 30),

    // ส่วนลดสูงสุด (% ของยอดรวม) ที่พนักงานที่ไม่ใช่ admin ใส่ได้ในใบเสนอราคา
    // ตั้งใน .env:  POS_MAX_DISCOUNT_PERCENT=10
    'max_discount_percent' => (float) env('POS_MAX_DISCOUNT_PERCENT', 10),

    'shift_grace_minutes' => (int) env('POS_SHIFT_GRACE_MINUTES', 15),
];
