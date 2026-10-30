<?php

return [
    // true = พนักงานที่ไม่ใช่ admin ต้องเปิดกะก่อนจึงขายได้ (POS และแปลงใบเสนอราคาเป็นบิล)
    // ตั้งใน .env:  POS_REQUIRE_OPEN_SHIFT=true
    'require_open_shift' => env('POS_REQUIRE_OPEN_SHIFT', false),
];
