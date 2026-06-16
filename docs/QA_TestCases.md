# Test Case: ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ ปีงบประมาณ 2569

**สร้างโดย:** QA Engineer + UX Tester Analysis  
**วันที่:** 2026-06-16  
**ขอบเขต:** Highland Eval, Island Eval, School Confirmation  
**ระดับความรุนแรง:** Critical / High / Medium / Low

---

## หมวดที่ 1: การกรอกข้อมูลไม่ครบ

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 1.1 | กดบันทึกแบบฟอร์ม Highland Eval โดยไม่กรอกข้อมูลใด | 1. Login เป็น school<br>2. เข้าหน้า Highland Eval<br>3. กดปุ่มบันทึกทันทีโดยไม่กรอกอะไร | (ฟอร์มว่างทั้งหมด) | ระบบบันทึกได้ (เพราะทุกช่องเป็น optional) และแสดงข้อความ "บันทึกสำเร็จ" — **แต่ควรมี warning** ว่ายังไม่ครบ | High | ไม่มี required field ใดใน highland eval → ข้อมูลว่างเปล่าถูกบันทึกได้ | เพิ่ม frontend warning เมื่อ field สำคัญยังว่าง (director_name, lat/lng) ก่อนบันทึก |
| 1.2 | กดบันทึก Island Eval โดยไม่เลือก citeria01 (เกาะ/ไม่เกาะ) | 1. เข้าหน้า Island Eval<br>2. ไม่เลือก radio citeria01<br>3. กดบันทึก | citeria01 = ไม่เลือก (null) | ระบบบันทึกด้วย citeria01=NULL → island_type=0 (ไม่ใช่เกาะ) โดยผู้ใช้ไม่รู้ตัว | **Critical** | citeria01 เป็น gate criteria แต่ไม่มี required attr; backend: NULL ≠ 1 ทำให้ island_type=0 | เพิ่ม `required` บน radio citeria01; แสดง warning "กรุณาเลือกว่าเป็นโรงเรียนบนเกาะหรือไม่" |
| 1.3 | กรอกข้อมูลหน้าแรก (ข้อมูลโรงเรียน) แล้วเปลี่ยน Tab โดยไม่กดบันทึก | 1. กรอกชื่อผู้อำนวยการ ที่อยู่<br>2. คลิก Tab "จำนวนนักเรียน"<br>3. กดปุ่มบันทึก | director_name="นายทดสอบ" แต่ยังอยู่ Tab 2 | ระบบบันทึกทุก field ในฟอร์มเดียวกัน (single form) ไม่มีการสูญหายของข้อมูล | Medium | ตรวจว่า tab navigation ไม่ทำให้ข้อมูล Tab อื่นหาย (เนื่องจาก single-page form) | แจ้งให้ชัดว่า "ทุก Tab อยู่ในฟอร์มเดียวกัน กดบันทึกเพียงครั้งเดียว" |
| 1.4 | เลือกโรงเรียนในหน้า select แต่ไม่กดเริ่ม | 1. เลือก SAO + โรงเรียนในหน้า select<br>2. ไม่กดปุ่ม "เริ่มประเมิน"<br>3. เปิด URL `/highland/eval` โดยตรง | URL: `/highland/eval` โดยไม่มี session | ระบบ redirect กลับหน้า select หรือแสดง 404 — ห้าม error/crash | High | Session sc_id ต้องถูก set ก่อนเข้าหน้า eval; ตรวจ session guard | เพิ่ม session guard ใน HighlandEvalController::edit() |
| 1.5 | กด Confirm School โดยไม่เลือก opened radio | 1. เข้าหน้า confirm/school<br>2. ไม่เลือก "โรงเรียนยังเปิดทำการ" / "ปิด"<br>3. กด "บันทึกร่าง" | opened = null | ระบบควรแจ้งเตือน "กรุณาระบุสถานะโรงเรียน" — ห้ามบันทึก null ลงฐานข้อมูล | **Critical** | `opened` เป็น key field ของ confirm; null ทำให้ตีความผิดพลาด | เพิ่ม required บน radio `opened`; backend validate `in_array($opened, [0,1])` |
| 1.6 | ผู้ใช้เลือกว่าโรงเรียน "ปิด" แต่ไม่เลือก close_type | 1. radio opened = 0 (ปิด)<br>2. ไม่เลือก close_type<br>3. กดบันทึก | opened=0, close_type=null | แจ้งเตือน "กรุณาระบุประเภทการปิด (ยุบ/รวม/ควบ)" | High | close_type conditional required เมื่อ opened=0 | เพิ่ม JS validation: ถ้า opened=0 ต้องเลือก close_type ก่อน submit |
| 1.7 | เลือก close_type=3 (ควบรวม) แต่ไม่กรอก merged_to | 1. opened=0, close_type=3<br>2. ไม่กรอกรหัสโรงเรียนที่ควบรวม<br>3. กดบันทึก | merged_to = "" | แจ้งเตือน "กรุณาระบุรหัสโรงเรียนที่รวมด้วย" | High | merged_to conditional required เมื่อ close_type=3 | เพิ่ม JS + server validation: merged_to required เมื่อ close_type=3 |
| 1.8 | กดบันทึก Highland Eval โดยไม่มีพิกัด lat/lng (ไม่ได้ปักหมุดแผนที่) | 1. ข้ามขั้นตอนแผนที่<br>2. เข้า eval โดยตรง<br>3. กดบันทึก | lat="", lng="", highest="" | ระบบบันทึกได้แต่คะแนน citeria01 (ความสูง) = 0; **ควรแจ้งเตือน** ว่ายังไม่ปักหมุด | High | citeria01 (ความสูง) = 0 เมื่อ lat/lng ว่าง; ทำให้คะแนนไม่ถูกต้อง | เพิ่ม warning "ยังไม่ได้บันทึกพิกัด กรุณาปักหมุดบนแผนที่ก่อน" |
| 1.9 | เพิ่มกลุ่มชาติพันธุ์ แต่ไม่ใส่จำนวน | 1. เลือก ethnic group จาก dropdown<br>2. ไม่กรอกจำนวน (newEthnicNum ว่าง)<br>3. กด "เพิ่ม" | hilltrib_number = "" | ระบบแจ้ง "กรุณากรอกจำนวนนักเรียนกลุ่มชาติพันธุ์" หรือตั้งค่า 0 | Medium | AJAX /highland/hilltrib/add: ตรวจว่า hilltrib_number ว่างถูก validate | เพิ่ม required + min=1 บน newEthnicNum; server validate integer > 0 |
| 1.10 | กรอกข้อมูล SAO/OBEC cert โดยไม่เลือก status | 1. Login SAO<br>2. เปิดหน้า cert highland/island<br>3. กดบันทึกโดยไม่เลือก sao_status | sao_status = ค่า default ("-") | ระบบแจ้ง "กรุณาเลือกผลการพิจารณา" ก่อนบันทึก | High | sao_status เป็น dropdown; ค่า default อาจเป็น 0 (รอพิจารณา) ซึ่งถูก save ได้ | เพิ่ม required + placeholder option ที่ value="" เพื่อบังคับให้เลือก |

---

## หมวดที่ 2: การกรอกข้อมูลผิดรูปแบบ

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 2.1 | กรอกตัวอักษรในช่อง stu_kinder (จำนวนนักเรียน) | 1. กรอก "abc" ในช่อง stu_kinder<br>2. กดบันทึก | stu_kinder = "abc" | Browser (type=number) block input; server cast เป็น 0 หรือ null — ห้าม error 500 | Medium | input type=number ป้องกัน browser-side; server: intval("abc")=0 | ตรวจสอบว่า PHP intval บน server ทำงานถูกต้อง; ไม่แสดง SQL error |
| 2.2 | กรอกค่าติดลบในช่องจำนวนนักเรียน | 1. กรอก -5 ใน stu_prim<br>2. กดบันทึก | stu_prim = -5 | ระบบแจ้ง "กรุณากรอกตัวเลขที่มากกว่าหรือเท่ากับ 0" หรือตัด min=0 อัตโนมัติ | High | stu_kinder ไม่มี min=0 ใน HTML; server: intval(-5) = -5 บันทึกลง DB ได้ | เพิ่ม min="0" บนทุก number field จำนวนนักเรียน/ครู; server validate >= 0 |
| 2.3 | กรอกจำนวนนักเรียนเป็นทศนิยม | 1. กรอก "12.5" ใน stu_prim<br>2. กดบันทึก | stu_prim = 12.5 | Browser แสดง error (type=number ไม่มี step); server เก็บ intval = 12 | Low | ตรวจว่า intval() บน server ตัดทศนิยมได้ถูกต้อง | เพิ่ม step="1" บนช่องจำนวนนักเรียน/ครู |
| 2.4 | กรอกเบอร์โทรผิดรูปแบบ (สั้นเกิน) | 1. กรอก "08123" ใน director_tel<br>2. กดบันทึก | director_tel = "08123" | JS auto-format ไม่ครบ 10 หลัก → แสดง "กรุณากรอกเบอร์โทร 10 หลัก" และห้าม submit | High | JS validation ใช้ pattern check ก่อน submit; server: normTel() ส่งผ่าน string ตามที่รับ | ยืนยันว่า server ไม่รับ format ที่ผิด; เพิ่ม server-side regex validate |
| 2.5 | กรอกเบอร์โทรเกิน 10 หลัก | 1. กรอก "081234567890123" ใน director_tel<br>2. กดบันทึก | director_tel = "081234567890123" | maxlength=12 บน HTML ป้องกัน; JS format ตัดอัตโนมัติ | Medium | ตรวจว่า maxlength=12 ทำงาน (รูปแบบ XXX-XXX-XXXX = 12 ตัวอักษร) | ยืนยัน maxlength=12 ทำงานบนทุก browser |
| 2.6 | กรอกอีเมลผิดรูปแบบในช่องชื่อ (ผู้ใช้สับสน) | 1. กรอก "test@email.com" ใน director_name<br>2. กดบันทึก | director_name = "test@email.com" | ระบบบันทึกได้ (ไม่มี email validation บน text field) — ควรตรวจสอบข้อมูลที่แสดงผล | Low | ช่อง director_name เป็น text ไม่มี format check; ข้อมูลแปลกถูกบันทึก | ไม่ต้องเพิ่ม validation เนื่องจากชื่ออาจมีได้หลายรูปแบบ |
| 2.7 | กรอกพิกัดผิดในช่อง lat/lng ของ Island Eval | 1. กรอก "999" ใน lat ของ island eval<br>2. กดบันทึก | lat = "999", lng = "-999" | ระบบบันทึกค่าได้ (field เป็น text ไม่มี range check); แผนที่แสดงผิด | **Critical** | lat range: -90 ถึง 90; lng: -180 ถึง 180; ไม่มี validation ใน island eval | เพิ่ม type="number" min="-90" max="90" บน lat; min="-180" max="180" บน lng + server validate |
| 2.8 | กรอกอักขระพิเศษในช่องชื่อโรงเรียน | 1. กรอก "@#$%^&*()" ใน sc_names<br>2. กดบันทึก | sc_names = "@#$%^&*()" | ระบบบันทึกได้ แต่ต้อง escape ก่อนแสดงผล (XSS risk) | High | ตรวจว่า View::e() ถูกใช้งานตอน render sc_names ทุกจุด | ใช้ htmlspecialchars ทุกจุดที่ render ข้อมูลจาก DB (View::e()) |
| 2.9 | กรอก emoji ในช่องข้อความ | 1. กรอก "โรงเรียน 😊🔥✅" ใน sc_names<br>2. กดบันทึก | sc_names = "โรงเรียน 😊🔥✅" | บันทึกได้ถ้า DB charset = utf8mb4; ถ้า utf8 จะ error 500 (emoji ใช้ 4 bytes) | High | ตรวจ DB charset: ต้องเป็น `utf8mb4` ไม่ใช่ `utf8` | ตั้งค่า `charset=utf8mb4` ใน PDO connection; alter table เป็น utf8mb4 |
| 2.10 | Copy ข้อมูลจาก Excel (มี tab/newline) | 1. Copy cell จาก Excel ที่มี tab/CR/LF<br>2. Paste ในช่อง sc_names หรือ address<br>3. กดบันทึก | sc_names = "โรงเรียน\t\nทดสอบ" | ระบบ trim/sanitize แท็บและขึ้นบรรทัดใหม่; แสดงผลเป็นบรรทัดเดียว | Medium | ตรวจว่า controller ทำ trim() บน string fields; ตรวจผลลัพธ์ที่บันทึก | เพิ่ม `strip_tags(trim($val))` บน text fields ใน save() |
| 2.11 | กรอกระยะทาง citeria041 ติดลบ | 1. กรอก "-5.5" ใน citeria041 (ระยะทาง km)<br>2. กดบันทึก | citeria041 = -5.5 | แจ้ง "ระยะทางต้องมากกว่าหรือเท่ากับ 0" | High | input step=0.01 ไม่มี min; server cast เป็น float → -5.5 บันทึกได้ | เพิ่ม min="0" บน citeria041, 05, 06, 07, 08; server validate >= 0 |
| 2.12 | กรอกปีการศึกษาผิดใน URL parameter | 1. แก้ URL: `/highland/eval?year=9999`<br>2. ดูว่าระบบโหลดข้อมูลปีใด | acadyears = 9999 (ใส่ใน param) | ระบบใช้ App::acadYear() (2569) ไม่ใช่ค่าจาก URL parameter; ปลอดภัย | Medium | ตรวจว่า acadyears ไม่ถูกรับจาก $_GET ใน controller | ยืนยันว่า acadYear() hardcoded จาก config — ไม่รับจาก user input |
| 2.13 | กรอกค่าสูงมากในช่องจำนวนนักเรียน | 1. กรอก 999999 ใน stu_prim<br>2. กดบันทึก | stu_prim = 999999 | ระบบบันทึกได้ (ไม่มี max check); คะแนนคำนวณอาจผิดพลาด | Medium | ตรวจ column type ใน DB: int(11) รองรับ 999999 ได้; แต่ค่าไม่สมจริง | เพิ่ม max="9999" บน number fields จำนวนนักเรียน/ครู |
| 2.14 | กรอกข้อมูลภาษาไทย อังกฤษ ตัวเลข ปนกันในช่อง moo | 1. กรอก "ที่ 3A หมู่" ใน moo<br>2. กดบันทึก | moo = "ที่ 3A หมู่" | ช่อง moo เป็น type=text (ไม่ใช่ number) บันทึกได้ตามที่กรอก | Low | ตรวจว่า moo ใน DB เป็น varchar ไม่ใช่ int | ไม่ต้อง validate เพิ่ม เนื่องจาก moo อาจมีรูปแบบหลากหลาย |

---

## หมวดที่ 3: การกรอกข้อมูลเกินขอบเขต

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 3.1 | กรอกชื่อโรงเรียนยาวมากกว่า 255 ตัวอักษร | 1. กรอกข้อความยาว 300 ตัวอักษรใน sc_names<br>2. กดบันทึก | sc_names = "ก" × 300 | ถ้า column เป็น VARCHAR(255) → MySQL truncate หรือ error; ไม่ควร crash | **Critical** | ตรวจ DB column type ของ sc_names; ไม่มี maxlength บน HTML input | เพิ่ม maxlength="255" บน sc_names; server substr($val, 0, 255) |
| 3.2 | กรอกคำอธิบาย sao_comment ยาวหลายพัน ตัวอักษร | 1. กรอกข้อความ 5000 ตัวอักษรใน sao_comment<br>2. กดบันทึก | sao_comment = "ก" × 5000 | บันทึกได้ถ้า column เป็น TEXT; ถ้าเป็น VARCHAR(255) จะ error | Medium | ตรวจ column type ของ sao_comment, spt_comment ใน DB | เปลี่ยน comment fields เป็น TEXT type ใน DB |
| 3.3 | กรอกจำนวนนักเรียนมากผิดปกติ | 1. กรอก 99999 ใน stu_kinder, stu_prim, stu_second, stu_high<br>2. กดบันทึก | stu_sum = 399996 | บันทึกได้; ตรวจว่า sum_score คำนวณไม่ overflow หรือ error | Medium | ScoreService::calcHighland ใช้ค่า hilltrib; ตรวจ division-by-zero เมื่อ stu_sum = 0 | เพิ่ม max validation; เพิ่มการตรวจ division-by-zero ใน ScoreService |
| 3.4 | กรอกระยะทาง citeria06 (ทะเล) มากเกิน 9999 km | 1. กรอก 99999 ใน citeria06 (island)<br>2. กดบันทึก | citeria06 = 99999 | บันทึกได้; ค่าไม่สมจริงสำหรับระยะทางในทะเลของไทย | Low | ตรวจ column type float/decimal; คะแนนยังคำนวณได้โดยไม่ error | เพิ่ม max="9999" บน distance fields |
| 3.5 | กรอกจำนวนครูมากกว่าจำนวนนักเรียน | 1. stu_prim=5, stu_sum=5<br>2. teacher=100, gov_employee=100<br>3. กดบันทึก | stu_sum=5, sum_teacher=200 | บันทึกได้ — แต่ควรมี warning "จำนวนครูมากกว่านักเรียนผิดปกติ" | Medium | ตรวจว่ามี cross-field validation หรือไม่ | เพิ่ม JS warning (ไม่ block): "จำนวนบุคลากรสูงกว่าจำนวนนักเรียนมาก กรุณาตรวจสอบ" |
| 3.6 | กรอกค่าเบี้ยเรือ citeria08 มากผิดปกติ | 1. กรอก 999999 ใน citeria08 (ค่าโดยสาร/เที่ยว)<br>2. กดบันทึก | citeria08 = 999999 | บันทึกได้; ค่าโดยสารไม่สมเหตุสมผล | Low | ตรวจ column type; ค่านี้ใช้ในการคำนวณคะแนน | เพิ่ม max="99999" บน citeria08 |
| 3.7 | กรอกจำนวนนักเรียนชาติพันธุ์เกินจำนวนนักเรียนทั้งหมด | 1. stu_sum=10<br>2. เพิ่ม ethnic group 50 คน<br>3. กดบันทึก | stu_hilltrib=50, stu_sum=10 | บันทึกได้; citeria11 (%) = 500% — ไม่สมเหตุสมผล | High | citeria11 = (stu_hilltrib / stu_sum) * 100; ถ้า stu_hilltrib > stu_sum คะแนนผิดพลาด | เพิ่ม validation: hilltrib_number รวมกันต้องไม่เกิน stu_sum; แสดง warning |
| 3.8 | กรอกจำนวน boarding stu_sleep_boy/girl มากผิดปกติ | 1. กรอก 9999 ใน citeria14 (นักเรียนนอน)<br>2. กดบันทึก | citeria14 = 9999 | บันทึกได้; ตรวจว่าคะแนน citeria14 ยังอยู่ในช่วงปกติ | Low | ดูสูตรคะแนน citeria14 ใน ScoreService | เพิ่ม max="9999" |

---

## หมวดที่ 4: การเลือกเมนูผิด / คลิกผิด

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 4.1 | กดปุ่มบันทึกซ้ำหลายครั้งติดกัน (double click) | 1. กรอกข้อมูลครบ<br>2. Double click ปุ่มบันทึก<br>3. หรือกดเร็วต่อเนื่อง | (ข้อมูลปกติ) | ข้อมูลถูกบันทึกเพียงครั้งเดียว — ห้าม duplicate insert | **Critical** | ไม่มี button disable หลัง click; upsert logic ใน controller; แต่ hilltrib อาจ insert ซ้ำ | เพิ่ม `disabled` บนปุ่มหลัง click ครั้งแรก; หรือใช้ JS debounce |
| 4.2 | กดปุ่ม "เพิ่มกลุ่มชาติพันธุ์" ซ้ำด้วยข้อมูลเดิม | 1. เลือก ethnic + จำนวน<br>2. กดเพิ่มซ้ำ 3 ครั้ง | hilltrib_id=1, num=20 × 3 ครั้ง | ระบบ upsert: ลบแล้ว insert ใหม่ — ไม่ duplicate; ตรวจว่า upsert ทำงาน | Medium | /highland/hilltrib/add: `DELETE ... WHERE (sc_id,acadyears,hilltrib)` แล้ว INSERT | ยืนยัน AJAX handler ทำ DELETE before INSERT |
| 4.3 | กดปุ่มย้อนกลับ browser ระหว่างกรอกข้อมูล | 1. กรอกข้อมูลบางส่วน<br>2. กดปุ่ม Back ของ browser<br>3. กด Forward กลับมา | (ข้อมูลกรอกค้างไว้) | Browser restore form data (bfcache) หรือข้อมูลหาย; ระบบไม่ crash | Medium | ตรวจว่า CSRF token ยังใช้งานได้หลัง back/forward (อาจ expire) | แสดง autosave indicator; หรือ warn ก่อน navigate ออกจากหน้า |
| 4.4 | กด Refresh (F5) ก่อนบันทึก | 1. กรอกข้อมูลบางส่วน<br>2. กด F5<br>3. Browser แสดง "Resubmit?" | (ข้อมูลที่ไม่ได้ save) | เฉพาะหน้า eval (GET): ข้อมูลหาย; ไม่ crash; ไม่ duplicate | Medium | ตรวจว่า GET form (filter) ไม่ prompt resubmit; POST form prompt resubmit ปกติ | ไม่ต้องแก้ไข (พฤติกรรม browser มาตรฐาน) |
| 4.5 | กด Refresh หลังบันทึกสำเร็จ (POST-Refresh) | 1. กรอกข้อมูลและกดบันทึก<br>2. กด F5 ทันที<br>3. Browser แสดง "Confirm resubmit?" | (submit ซ้ำ) | ระบบ upsert ป้องกัน duplicate; ข้อมูล update ด้วยค่าเดิม | High | ตรวจว่า POST/Redirect/GET pattern ถูกใช้หลัง save สำเร็จ | เพิ่ม Post-Redirect-Get: redirect to GET หลัง save สำเร็จ |
| 4.6 | กดปุ่มลบกลุ่มชาติพันธุ์โดยไม่ได้ตั้งใจ | 1. กด × ลบ ethnic group<br>2. ไม่มี confirmation | — | ลบทันที; ไม่มี undo | Medium | /highland/hilltrib/delete: DELETE โดยตรง ไม่มี confirm | เพิ่ม confirmation dialog "ยืนยันลบกลุ่มชาติพันธุ์นี้?" |
| 4.7 | เปิดหลายแท็บแก้ไขโรงเรียนเดียวกันพร้อมกัน | 1. เปิด highland eval Tab 1<br>2. เปิด Tab 2 highland eval โรงเรียนเดียวกัน<br>3. แก้ต่างกัน บันทึกทั้งคู่ | Tab1: director_name="A"; Tab2: director_name="B" | Tab ที่บันทึกทีหลังชนะ (last write wins); ข้อมูล Tab แรกถูกเขียนทับ | High | ไม่มี optimistic locking หรือ conflict detection | เพิ่ม last_modified timestamp; ตรวจก่อน save ว่าข้อมูลถูกแก้โดยคนอื่นหรือยัง |
| 4.8 | กดเมนู Island Eval ขณะอยู่ระหว่างกรอก Highland Eval | 1. กรอก highland eval บางส่วน<br>2. คลิก sidebar ไปที่ Island<br>3. กลับมา highland | (ข้อมูลกรอกค้าง) | Browser navigate ออก: ข้อมูลหาย (ไม่มี autosave); ไม่ crash | Medium | ตรวจว่า session sc_id ยังอยู่เมื่อกลับมา | เพิ่ม `beforeunload` event warning "คุณมีข้อมูลที่ยังไม่บันทึก" |
| 4.9 | กดปุ่ม "ส่งยืนยัน" แทน "บันทึกร่าง" โดยไม่ได้ตั้งใจ | 1. กรอกข้อมูลไม่ครบ<br>2. กดปุ่ม "ส่งยืนยัน" (action=submit)<br>3. ข้อมูล lock ทันที | action = "submit" | ระบบ lock ข้อมูล; โรงเรียนแก้ไขไม่ได้; ต้องให้ SAO unlock | **Critical** | submitted=1 หลัง action=submit; ไม่สามารถแก้เองได้ | เพิ่ม confirmation dialog "เมื่อส่งยืนยันแล้วจะแก้ไขไม่ได้ — ยืนยันหรือไม่?"; ตรวจ validation ก่อน submit |
| 4.10 | เข้า URL ของโรงเรียนอื่นโดยตรง | 1. Login เป็นโรงเรียน A (sc_id=111)<br>2. แก้ URL: `/highland/eval?sc_id=222`<br>3. กดบันทึก | sc_id = 222 (ไม่ใช่ของตัวเอง) | ระบบปฏิเสธ: "ไม่มีสิทธิ์เข้าถึงโรงเรียนนี้" (403) | **Critical** | Auth::canAccessSchool($scId) ต้องตรวจ sc_id ทุก request | ยืนยันว่า canAccessSchool ถูกเรียกใน HighlandEvalController::save() |
| 4.11 | Login เป็น SAO แล้วเข้า URL ของ SAO อื่น | 1. Login SAO เขต A<br>2. แก้ URL เพื่อดูโรงเรียนใน SAO B<br>3. กดบันทึก cert | (school ของ SAO B) | ระบบปฏิเสธ: "ไม่มีสิทธิ์" | **Critical** | Auth::canAccessSchool() ตรวจสอบ sao_code ของ SAO | ยืนยัน sao_code check ทำงานใน cert controllers |
| 4.12 | กด Enter ในฟอร์มแล้ว submit โดยไม่ได้ตั้งใจ | 1. กรอกข้อมูลบางส่วน<br>2. กด Enter ในช่องข้อความ | (ข้อมูลไม่ครบ) | ฟอร์มส่งข้อมูล (default browser behavior) — อาจบันทึก draft ไม่ครบ | Medium | ตรวจ default submit button ของ form | เพิ่ม `type="button"` บนปุ่มที่ไม่ต้องการ submit; ปุ่มบันทึกใช้ `type="submit"` |

---

## หมวดที่ 5: การค้นหาและกรองข้อมูลผิดพลาด

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 5.1 | ค้นหาชื่อโรงเรียนที่ไม่มีในระบบ | 1. กรอก "โรงเรียนจันทรา" ใน search<br>2. กดค้นหา | q = "โรงเรียนจันทรา" | แสดง "ไม่พบข้อมูล" — ห้าม error/crash/empty page | Medium | ตรวจว่า SchoolController::schools() คืน [] เมื่อไม่พบข้อมูล | แสดง empty state "ไม่พบโรงเรียนที่ค้นหา" |
| 5.2 | ค้นหาด้วยช่องว่างอย่างเดียว | 1. กรอก "   " (spaces) ใน search<br>2. กดค้นหา | q = "   " | ระบบ trim และค้นหาด้วย "" → แสดงทั้งหมด หรือแสดง error | Medium | ตรวจว่า query trim() ก่อน LIKE %q% | server: `$q = trim($_GET['q'] ?? '')` ก่อน query |
| 5.3 | ค้นหาด้วย SQL injection ใน search box | 1. กรอก `' OR '1'='1` ในช่อง q<br>2. กดค้นหา | q = "' OR '1'='1" | แสดงผลเป็น literal string หรือไม่พบ — ห้าม return all records | **Critical** | ตรวจว่า SchoolController ใช้ prepared statement สำหรับ LIKE | ยืนยัน PDO prepared statement ใน models; ห้ามใช้ string concatenation |
| 5.4 | ค้นหาด้วยตัวอักษร 1 ตัว | 1. กรอก "ก" ใน q<br>2. กดค้นหา | q = "ก" | แสดงผลโรงเรียนทุกที่มี "ก" ในชื่อ — อาจช้าถ้ามีข้อมูลมาก | Low | ตรวจ response time; ตรวจว่ามี LIMIT ใน query | เพิ่ม LIMIT 100 ใน search query |
| 5.5 | กรองข้อมูล confirm list ด้วยหลาย filter พร้อมกัน | 1. กรอง province + confirmed=1 + sao_status=2<br>2. กดกรอง | province="สงขลา", confirmed=1, sao_status=2 | แสดงผลที่ตรงเงื่อนไขทั้งหมด — ห้าม error; ผลลัพธ์ถูกต้อง | High | ตรวจ SQL WHERE clause รวม multiple conditions; ตรวจ AND/OR logic | ทดสอบ query ด้วย EXPLAIN ว่า index ถูกใช้ |
| 5.6 | กดล้าง filter แล้ว refresh | 1. กรองข้อมูล<br>2. กดล้าง filter<br>3. กด refresh | (หลัง clear) | แสดงข้อมูลทั้งหมด; ไม่ cache filter เดิม | Low | ตรวจว่า clear filter reset GET params ถูกต้อง | ใช้ redirect to base URL เมื่อ clear |
| 5.7 | เลือก province ในการค้นหา AJAX schools แล้ว change | 1. เลือก sao_id (SAO เขต)<br>2. รอ dropdown schools โหลด<br>3. เปลี่ยน sao_id<br>4. dropdown schools ล้างหรือโหลดใหม่ | sao_id เปลี่ยนหลาย ครั้ง | dropdown schools refresh ตาม sao_id ที่เลือกล่าสุด — ห้ามแสดงข้อมูลผสม | High | ตรวจ JS AJAX call ว่า cancel previous request เมื่อมี request ใหม่ | ใช้ AbortController หรือ request ID เพื่อยกเลิก request เก่า |
| 5.8 | ค้นหาโรงเรียนที่ถูก lock submission ก่อน select | 1. Admin เลือกโรงเรียนที่ submitted=1<br>2. กด start ประเมิน | (โรงเรียน locked) | ระบบบล็อกหรือแสดง read-only mode — ห้ามให้แก้ไขโดยไม่ unlock ก่อน | Medium | ตรวจว่า select/start logic ตรวจ submitted status ก่อนอนุญาต | แสดง badge "ส่งแล้ว" บนโรงเรียนที่ submitted=1 ใน dropdown |

---

## หมวดที่ 6: การแนบไฟล์ผิดพลาด

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 6.1 | แนบไฟล์ .exe | 1. เลือกไฟล์ test.exe ใน refdoc_04<br>2. กดบันทึก | file: test.exe | Browser block (accept filter); แต่ถ้า bypass → server ต้องปฏิเสธ | **Critical** | ตรวจ server MIME check; accept=".pdf,.jpg,.jpeg,.png" เป็น client-only | เพิ่ม server-side: `$allowedMime = ['application/pdf','image/jpeg','image/png']` |
| 6.2 | แนบไฟล์ .php (อันตราย) | 1. Rename test.php เป็น test.jpg<br>2. อัปโหลด "test.jpg" ที่จริงเป็น PHP | file: test.php (rename .jpg) | ระบบต้องตรวจ MIME type จริง ไม่ใช่แค่ extension — ห้าม execute script | **Critical** | ตรวจ finfo_file() หรือ mime_content_type() ใน Upload class | ใช้ `finfo` ตรวจ MIME จริง; บันทึก path นอก webroot; ห้าม execute uploads |
| 6.3 | แนบไฟล์ขนาดใหญ่เกิน 10MB | 1. เลือกไฟล์ PDF 20MB<br>2. กดบันทึก | file: 20MB PDF | แจ้ง "ไฟล์ขนาดใหญ่เกินกำหนด (สูงสุด 5MB)" | High | ตรวจ PHP upload_max_filesize / post_max_size; ไม่มี size check ใน code | เพิ่ม JS pre-check ขนาดไฟล์; server: `$_FILES['size'] > MAX` |
| 6.4 | แนบไฟล์ชื่อภาษาไทยยาว | 1. เลือกไฟล์ "รายงานผลการดำเนินงานโรงเรียนบ้านทดสอบ2569.pdf"<br>2. กดบันทึก | filename: Thai 50+ chars | บันทึกได้; path ถูกสร้างเป็น `{scId}_citeria{nn}` (ชื่อ original ไม่ใช้) | Low | ตรวจว่า filename ถูก sanitize ก่อน save; ดู Upload::save() | ยืนยัน filename pattern `{scId}_criteria{nn}.{ext}` ไม่ใช้ original name |
| 6.5 | แนบไฟล์แล้วแก้ไขหน้าก่อนบันทึก | 1. เลือกไฟล์ใน refdoc_04<br>2. แก้ข้อมูล Tab 1<br>3. กดบันทึก | file ใน memory + data | บันทึกไฟล์และข้อมูลพร้อมกัน — ห้ามไฟล์ หายหรือข้อมูลหาย | Medium | ตรวจว่า multipart/form-data ส่งทั้ง fields + files พร้อมกัน | ตรวจว่า form enctype="multipart/form-data" ครบทุกหน้า eval |
| 6.6 | กดบันทึกโดยไม่แนบไฟล์ในช่องที่มีไฟล์เดิม | 1. มีไฟล์เดิมอยู่ใน refdoc_04<br>2. ไม่แนบไฟล์ใหม่<br>3. กดบันทึก | file input ว่าง | ระบบเก็บไฟล์เดิมไว้ — ห้าม overwrite ด้วย null | High | ตรวจ controller: `if($_FILES['refdoc_04']['size'] > 0)` ก่อน update path | ยืนยัน upload logic ไม่ลบไฟล์เดิมเมื่อไม่มีการ upload ใหม่ |
| 6.7 | แนบไฟล์เสียหาย (0 bytes หรือ corrupt) | 1. สร้างไฟล์ PDF ขนาด 0 bytes<br>2. แนบและบันทึก | file: 0 bytes PDF | แจ้ง "ไฟล์ไม่สามารถใช้งานได้" หรือบันทึก path โดยไม่ error | Medium | `$_FILES['size'] === 0` → ตรวจว่า handle อย่างไร | เพิ่ม check: `if($_FILES['size'] === 0) → skip or error` |
| 6.8 | แนบไฟล์ชื่อมีสัญลักษณ์พิเศษ | 1. Rename ไฟล์เป็น "test <script>.pdf"<br>2. Upload | filename: "test <script>.pdf" | ระบบ sanitize filename ก่อนบันทึก — ห้ามเก็บ filename อันตราย | High | ตรวจ Upload::save() ว่า sanitize filename หรือใช้ชื่อใหม่เลย | ใช้ชื่อไฟล์ที่ generate เอง: `{scId}_citeria{nn}.{ext}` เท่านั้น |

---

## หมวดที่ 7: ความสัมพันธ์ข้อมูลเชิงตรรกะ

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 7.1 | Island: citeria01=0 (ไม่ใช่เกาะ) แต่กรอกข้อมูลเกาะทั้งหมด | 1. เลือก citeria01=0<br>2. กรอก citeria05 (ระยะทางทะเล) ทุกช่อง<br>3. กดบันทึก | citeria01=0, citeria05=25.5 | island_type=0 (ไม่ผ่าน); ข้อมูลบันทึกแต่คะแนนเป็น 0 — ควรแจ้ง warning | **Critical** | backend gate: citeria01≠1 → island_type=0; ผลต่อการรับรอง | แสดง alert ทันทีที่เลือก citeria01=0 "โรงเรียนนี้จะไม่ได้รับการรับรองเป็นเกาะ" |
| 7.2 | Highland: ระบุ citeria08=ไม่มีไฟฟ้า แต่ citeria10=มีอินเทอร์เน็ต | 1. ไม่เลือก checkbox citeria08<br>2. เลือก citeria10 (internet)<br>3. กดบันทึก | citeria08=[], citeria10=[1] | บันทึกได้ — แต่ logic ขัดแย้งกัน (ไม่มีไฟฟ้าแต่มีเน็ต) | Medium | ไม่มี cross-field logic validation | เพิ่ม JS warning: "มีอินเทอร์เน็ตแต่ไม่มีไฟฟ้า — กรุณาตรวจสอบ" |
| 7.3 | Highland: ระบุ citeria04=ไม่มีถนน 2 ล้อ แต่ citeria06=มีขนส่งสาธารณะ | 1. citeria04=1 (มีถนน)<br>2. citeria06=0 (ไม่มีขนส่ง)<br>3. กดบันทึก | citeria04=1, citeria06=0 | บันทึกได้; ตรวจว่า ScoreService คำนวณถูกตาม combination นี้ | Medium | ดูสูตรคะแนน citeria04+06 ใน ScoreService | ตรวจสูตรคะแนน |
| 7.4 | กรอก stu_sum=0 แต่มี ethnic group นักเรียน 20 คน | 1. stu_kinder/prim/second/high = 0<br>2. เพิ่ม hilltrib 20 คน<br>3. กดบันทึก | stu_sum=0, hilltrib_num=20 | citeria11 = 20/0*100 → **Division by zero!** | **Critical** | ScoreService::calcHighland() มี division-by-zero ที่ stu_sum=0 | เพิ่ม guard: `$pct = $stu_sum > 0 ? ($hilltrib/$stu_sum)*100 : 0` |
| 7.5 | Confirm: โรงเรียนระบุ opened=0 (ปิด) แต่กรอกจำนวนนักเรียน | 1. opened=0 (ปิด)<br>2. กรอก std_male=50, std_female=40<br>3. กดบันทึก | opened=0, std_total=90 | บันทึกได้ — แต่ logic ขัดแย้ง (ปิดแล้วแต่มีนักเรียน) | Medium | ไม่มี cross-field validation | เพิ่ม warning: "โรงเรียนปิดแล้ว แต่ระบุจำนวนนักเรียน — กรุณาตรวจสอบ" |
| 7.6 | Highland: กรอกความสูง citeria01 ต่ำมาก (50 เมตร) แต่อยู่ภาคเหนือ | 1. lat/lng ใน เชียงราย<br>2. citeria01=50 เมตร<br>3. กดบันทึก | highest=50, province="เชียงราย" | บันทึกได้; highland_type อาจเป็น 0 (ไม่ผ่าน 500 เมตร) | High | highland_type threshold = 500 ม. จาก config; citeria01=50 → score ต่ำ | แสดง real-time indicator "ความสูง 50 ม. ต่ำกว่าเกณฑ์ 500 ม." |
| 7.7 | Island: กรอก citeria07 (เวลาเดินทางทางเรือ) = 0 นาที | 1. citeria07 = 0<br>2. กดบันทึก | citeria07 = 0 (เวลา 0 นาที) | บันทึกได้; ค่า 0 อาจหมายถึง "ติดชายฝั่ง" → score ต่ำ | Low | ตรวจสูตร IslandScoreService::calcIsland() สำหรับ citeria07=0 | เพิ่ม tooltip อธิบายว่า "0 = ติดชายฝั่ง, ไม่มีระยะทาง" |
| 7.8 | Confirm: std_female กรอกติดลบ (มี min=0 ใน HTML แต่ bypass ได้) | 1. Inspect Element แก้ min=0 เป็น min=-999<br>2. กรอก -10<br>3. กดบันทึก | std_female = -10 | Server ต้องปฏิเสธ: validate >= 0; ห้ามบันทึกค่าติดลบ | High | server: `max(0, (int)$data['std_female'])` ใน ConfirmController | ยืนยัน server-side min=0 validation ทำงาน; client-side เพียงอย่างเดียวไม่พอ |

---

## หมวดที่ 8: ความปลอดภัยเบื้องต้นของฟอร์ม

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 8.1 | XSS ใน director_name | 1. กรอก `<script>alert('XSS')</script>` ใน director_name<br>2. บันทึก<br>3. เปิดหน้าดูข้อมูล | director_name = "<script>alert('XSS')</script>" | ข้อมูลแสดงเป็น `&lt;script&gt;...` — ห้าม execute script | **Critical** | ตรวจทุกจุดที่ render director_name ว่าใช้ `View::e()` หรือ `htmlspecialchars()` | ตรวจ eval.php ทุก `<?= $data['director_name'] ?>` ต้องเป็น `<?= View::e($data['director_name']) ?>` |
| 8.2 | XSS ใน sao_comment (ช่องความเห็น) | 1. Login SAO<br>2. กรอก `<img src=x onerror=alert(1)>` ใน sao_comment<br>3. บันทึก<br>4. Admin เปิดหน้า cert list | sao_comment = "<img src=x onerror=alert(1)>" | แสดงเป็น literal HTML ที่ escaped — ห้าม execute | **Critical** | ตรวจ confirm/list.php และ eval.php ว่า escape comment fields | ตรวจ render sao_comment ในทุก view |
| 8.3 | SQL Injection ใน search parameter | 1. URL: `/highland/cert?q=' OR 1=1 --`<br>2. กด Enter | q = "' OR 1=1 --" | แสดงผลตาม literal string หรือไม่พบ — ห้าม leak data | **Critical** | ตรวจ SchoolController::schools() ใช้ PDO prepared statement + LIKE ? | ยืนยัน `Db::all("...WHERE sc_names LIKE ?", ["%$q%"])` |
| 8.4 | SQL Injection ใน sc_id parameter | 1. URL: `/highland/eval?sc_id=1 OR 1=1`<br>2. กดบันทึก | sc_id = "1 OR 1=1" | Auth::canAccessSchool("1 OR 1=1") → false; ปฏิเสธ request | **Critical** | ตรวจว่า sc_id ถูก cast เป็น int ก่อนใช้ใน query | เพิ่ม `(int)$_POST['sc_id']` ทุกจุดที่รับ sc_id |
| 8.5 | Path Traversal ใน file download | 1. แก้ URL: `/uploads/highland/2569/../../../config/config.php`<br>2. กด Enter | path = "../../config/config.php" | 403 Forbidden หรือ file not found — ห้าม serve config | **Critical** | ตรวจ .htaccess block `/config/` และ `/app/`; ตรวจ file serving | ยืนยัน .htaccess `Deny from all` บน config/ และ app/ |
| 8.6 | CSRF Attack (ส่ง POST จาก domain อื่น) | 1. สร้าง HTML form บน domain อื่น POST ไป `/confirm/school`<br>2. เปิดใน browser ที่ login อยู่แล้ว | CSRF token ไม่ถูกต้อง | ระบบปฏิเสธ: "CSRF token invalid" (403) | **Critical** | Csrf::verify() ต้องเรียกใน ConfirmController::school() | ยืนยัน `Csrf::verify()` ถูกเรียก **ก่อน** ทุก operation ใน POST handlers |
| 8.7 | Brute Force รหัสผ่าน Login | 1. ลอง login ผิด 100 ครั้ง<br>2. ดูว่าถูก rate limit หรือไม่ | password = "wrong" × 100 | ควรแสดง CAPTCHA หรือ rate limit หลังผิด N ครั้ง | High | ตรวจ Auth::attempt() ว่ามี rate limiting หรือไม่ (ปัจจุบันไม่มี) | เพิ่ม login attempt counter ใน session; block 15 นาทีหลังผิด 5 ครั้ง |
| 8.8 | Direct URL access ข้าม auth | 1. Logout แล้วเข้า `/highland/eval` โดยตรง | (ไม่มี session) | Redirect ไปหน้า login — ห้ามแสดงข้อมูล | **Critical** | Auth::require() ต้องอยู่ใน HighlandEvalController::edit() | ยืนยัน `Auth::require(['school','sao','admin'])` ใน controller ทุก method |
| 8.9 | กรอก `../../etc/passwd` ในช่อง sc_names | 1. กรอก `../../etc/passwd` ใน sc_names<br>2. กดบันทึก | sc_names = "../../etc/passwd" | บันทึกเป็น text ปกติ — ไม่มีผลต่อ filesystem | Low | ข้อมูลนี้ไม่ถูกนำไปใช้เป็น path | ยืนยันว่า sc_names ไม่เคยถูกใช้เป็น file path |
| 8.10 | Session fixation / hijacking | 1. Copy session cookie จาก browser A<br>2. ใช้ใน browser B<br>3. ดูว่า login ได้หรือไม่ | PHPSESSID จาก session ที่ active | ควรมี session regeneration หลัง login | High | ตรวจ Auth::attempt() ว่า `session_regenerate_id(true)` หลัง login | เพิ่ม `session_regenerate_id(true)` ใน Auth::attempt() หลัง login สำเร็จ |

---

## หมวดที่ 9: การใช้งานบนอุปกรณ์และ Browser ต่างๆ

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 9.1 | ใช้มือถือ (viewport 390px) กรอกแบบฟอร์ม Eval | 1. เปิดบน iPhone/Android<br>2. กรอกทุก Tab<br>3. กดบันทึก | (ข้อมูลปกติ) | แบบฟอร์มแสดงถูกต้อง ไม่ overflow; ปุ่มกดได้; Tab ใช้งานได้ | High | ตรวจ responsive design; ตรวจ Tab scroll บน mobile | เพิ่ม `.overflow-x-auto` บน tablist; ตรวจ `touch-action` |
| 9.2 | กรอกด้วยคีย์บอร์ดอย่างเดียว (Tab navigation) | 1. กด Tab เพื่อเลื่อน field<br>2. กด Space สำหรับ checkbox/radio<br>3. กด Enter เพื่อ submit | (keyboard only) | Focus เคลื่อนไปทุก field ตามลำดับ; ไม่ข้าม field | Medium | ตรวจ tabindex; ตรวจ focus visible | ตรวจ outline ของ focused element ชัดเจน; เพิ่ม keyboard trap avoidance |
| 9.3 | ทดสอบบน Safari (webkit) | 1. เปิดบน Safari iOS/macOS<br>2. ทดสอบ date input, file upload<br>3. กดบันทึก | — | ทุก feature ทำงาน เหมือน Chrome | Medium | Safari มีความแตกต่างบน `type=number`, `accept` attribute, `date` input | ทดสอบ accept filter บน Safari (Safari บางเวอร์ชัน ignore accept) |
| 9.4 | ทดสอบบน Edge (Chromium) | 1. เปิดบน Microsoft Edge<br>2. ทดสอบทุกฟอร์ม | — | ทำงานเหมือน Chrome | Low | Edge เป็น Chromium-based; ส่วนใหญ่ compatible | — |
| 9.5 | หน้าจอเล็กมาก (320px) | 1. Resize browser เป็น 320px กว้าง<br>2. ดูทุกหน้า | — | ไม่มีเนื้อหา overflow นอกหน้าจอ; scroll ได้ตามแนวตั้ง | Medium | ตรวจ CSS breakpoints; table ใน confirm list อาจล้น | เพิ่ม `overflow-x-auto` wrapper บน table |
| 9.6 | กรอกด้วยคีย์บอร์ด Thai (Kedmanee/Pattachote) | 1. ตั้งค่า keyboard ไทย<br>2. กรอกชื่อภาษาไทย | sc_names = "โรงเรียนบ้านทดสอบ" | กรอกและแสดงผลถูกต้อง; ไม่มี encoding issue | Low | ตรวจ charset = UTF-8 ทุกจุด (HTTP header, meta charset, DB, PDO) | ยืนยัน `charset=utf8mb4` ใน PDO และ HTTP header |
| 9.7 | กรอก pin หมุดแผนที่บนมือถือ | 1. เปิด Google Maps embed บนมือถือ<br>2. แตะแผนที่เพื่อ pin<br>3. ตรวจว่า lat/lng ถูก save | (ใช้ touch) | แผนที่รับ touch event และส่ง lat/lng ผ่าน AJAX ได้ | High | ตรวจ Google Maps touch event; AJAX /map/savelatlng ทำงาน | ทดสอบ touch ใน Chrome DevTools mobile emulation |
| 9.8 | Zoom หน้าจอ 150-200% | 1. กด Ctrl+= ขยายหน้าจอ 150%<br>2. ใช้งานฟอร์ม | — | ไม่มีปุ่มซ้อนกัน; ข้อความไม่ถูกตัด; modal ยังใช้ได้ | Low | ตรวจ layout ที่ zoom; ปุ่มบันทึกต้องยังคลิกได้ | — |

---

## หมวดที่ 10: การจัดการ Error และข้อความแจ้งเตือน

| # | กรณีทดสอบ | ขั้นตอนการทดสอบ | ข้อมูลที่ใช้ทดสอบ | ผลลัพธ์ที่คาดหวัง | ความรุนแรง | สิ่งที่ต้องตรวจในระบบ | ข้อเสนอแนะ |
|---|-----------|----------------|-------------------|-------------------|-----------|----------------------|------------|
| 10.1 | ปิด Database ขณะใช้งาน | 1. Stop MySQL service<br>2. กดบันทึกข้อมูล | — | แสดง "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่" — ห้ามแสดง SQL error | **Critical** | Db.php catch PDOException → แสดงอะไรกับ user? | เพิ่ม global exception handler แสดง generic error; log จริงในไฟล์ logs/ |
| 10.2 | Token หมดอายุ / CSRF ผิด | 1. เปิดฟอร์มทิ้งไว้ 1 ชั่วโมง<br>2. กดบันทึก | CSRF token expired | แสดง "Session หมดอายุ กรุณา refresh หน้าและลองใหม่" พร้อม link reload | High | Csrf::verify() throw exception → จะแสดงอะไร? | ตรวจ CSRF error handler; แสดง friendly message ไม่ใช่ 500 |
| 10.3 | Upload folder ไม่มี permission เขียน | 1. chmod 444 บน uploads/highland<br>2. แนบไฟล์แล้วบันทึก | refdoc_04 = valid PDF | แสดง "ไม่สามารถบันทึกไฟล์ได้ กรุณาติดต่อผู้ดูแลระบบ" — ห้าม 500 | High | Upload::save() → PHP warning ถ้า move_uploaded_file ล้มเหลว | try-catch รอบ move_uploaded_file; return error message ชัดเจน |
| 10.4 | Network timeout ระหว่าง AJAX save | 1. ปิด network ขณะ AJAX กำลัง POST<br>2. ดู error handling | (network cut) | แสดง toast error "ไม่สามารถเชื่อมต่อได้ กรุณาตรวจสอบการเชื่อมต่อ" | High | ตรวจ fetch().catch() ใน AJAX calls | เพิ่ม catch block บน AJAX: แสดง error toast + ปุ่ม retry |
| 10.5 | กรอกผิดแล้ว error ข้อมูลที่กรอกไว้ต้องไม่หาย | 1. กรอก form ยาวมาก<br>2. เกิด validation error<br>3. ดูว่าข้อมูลยังอยู่ไหม | (validation fail) | หน้าโหลดใหม่พร้อม repopulate ข้อมูลที่กรอก + แสดง error สีแดง | **Critical** | ตรวจ controller: ถ้า save ล้มเหลว ส่ง data กลับไปยัง view ไหม? | เพิ่ม error repopulation: `return View::render('...', ['old' => $_POST, 'errors' => $errors])` |
| 10.6 | Error message ภาษาทางเทคนิค | 1. trigger database error<br>2. ดู error message | (DB error) | ข้อความต้องเป็นภาษาไทย ไม่มี SQL/stack trace | **Critical** | ตรวจทุก catch block ว่าแสดง friendly message | เพิ่ม `ini_set('display_errors', 0)` บน production; log ใน file แทน |
| 10.7 | บันทึกสำเร็จต้องมี feedback | 1. กรอกข้อมูลครบ<br>2. กดบันทึก | (ข้อมูลถูกต้อง) | แสดง toast/alert "บันทึกข้อมูลสำเร็จ" ชัดเจน และหน้าไม่ scroll หาย | High | ตรวจ Flash::success() ถูก render ใน layout หรือไม่ | ยืนยัน flash message ปรากฏหลัง redirect; ใช้สีเขียวชัดเจน |
| 10.8 | Submit ซ้ำระหว่าง loading | 1. กดบันทึก<br>2. กดซ้ำทันที (ก่อน response กลับ) | (race condition) | ส่ง request เพียง 1 ครั้ง; ปุ่ม disabled ระหว่าง loading | High | ตรวจว่า JS disable button หลัง click แรก | เพิ่ม `btn.disabled = true` + spinner ใน submit handler |
| 10.9 | Session expired กลางหน้า (idle 2+ ชั่วโมง) | 1. เปิดฟอร์ม<br>2. ทิ้งไว้ 2+ ชั่วโมง<br>3. กดบันทึก | session_lifetime expired | Redirect ไปหน้า login พร้อม flash "กรุณา login ใหม่" | High | ตรวจ session timeout config; Auth::require() redirect เมื่อ session null | ตั้ง `session.gc_maxlifetime` และแจ้ง user ก่อน expire |
| 10.10 | 404 สำหรับ route ที่ไม่มี | 1. เข้า URL: `/highland/unknown`<br>2. ดูผลลัพธ์ | — | แสดงหน้า 404 แบบ friendly ไม่ใช่ Apache/PHP default | Low | ตรวจ Router unmatched → 404 view; ตรวจว่ามี 404.php view | สร้าง app/Views/errors/404.php ที่เป็นมิตรกับผู้ใช้ |

---

## ส่วนที่ 1: รายการช่องข้อมูลที่ควรมี Validation

| Field | Form | บังคับ? | ชนิดข้อมูล | Min | Max | MaxLength | Format | Server Validate ปัจจุบัน | ควรเพิ่ม |
|-------|------|---------|-----------|-----|-----|-----------|--------|--------------------------|---------|
| `citeria01` (island gate) | Island Eval | **YES** | int | 0 | 1 | — | radio | ❌ ไม่มี | required radio; server: in_array([0,1]) |
| `opened` | Confirm | **YES** | int | 0 | 1 | — | radio | ❌ ไม่มี | required; server: must be 0 or 1 |
| `director_tel` | ทุก form | No | string | — | — | 12 | XXX-XXX-XXXX | ✅ JS format | server: regex /^\d{3}-\d{3}-\d{4}$/ ถ้ามีค่า |
| `stu_kinder/prim/second/high` | ทุก eval | No | int | 0 | 9999 | — | number | ❌ ไม่มี min/max | min=0, max=9999 on HTML; server: >= 0 |
| `stu_hilltrib` sum | Highland | No | int | — | ≤stu_sum | — | — | ❌ ไม่มี cross check | server: sum(hilltrib_number) ≤ stu_sum |
| `citeria041` (ระยะทาง km) | Highland | No | float | 0 | 999 | — | 0.01 step | ❌ ไม่มี | min="0" max="999"; server >= 0 |
| `citeria05,06` (ระยะทางเกาะ) | Island | No | float | 0 | 999 | — | 0.01 step | ❌ ไม่มี | min="0"; server >= 0 |
| `citeria07` (เวลาเรือ นาที) | Island | No | int | 0 | 9999 | — | — | ❌ ไม่มี | min="0" max="9999" |
| `citeria08` (ค่าโดยสาร) | Island | No | float | 0 | 99999 | — | — | ❌ ไม่มี | min="0" max="99999" |
| `lat` | Island Eval | No | float | -90 | 90 | — | decimal | ❌ ไม่มี | type="number" min="-90" max="90" |
| `lng` | Island Eval | No | float | -180 | 180 | — | decimal | ❌ ไม่มี | type="number" min="-180" max="180" |
| `sc_names` | ทุก eval | No | string | — | — | 255 | text | ❌ ไม่มี | maxlength="255"; server: substr(0,255) |
| `sao_comment/spt_comment` | Cert | No | string | — | — | 1000 | text | ❌ ไม่มี | maxlength="1000"; DB: TEXT type |
| `std_male/female` | Confirm | No | int | 0 | 9999 | — | number | ✅ max(0,...) | เพิ่ม max=9999 บน HTML |
| `tch_govt/hire/deputy/director` | Confirm | No | int | 0 | 999 | — | number | ✅ max(0,...) | เพิ่ม max=999 บน HTML |
| `merged_to_name` | Confirm | Conditional | string | — | — | 255 | text | ✅ substr(0,255) | required เมื่อ close_type=3 |
| `close_type` | Confirm | Conditional | int | 1 | 3 | — | radio | ❌ implicit | required เมื่อ opened=0; server: in_array |
| `newEthnicNum` | Highland Hilltrib | No | int | 1 | 9999 | — | number | ❌ ไม่มี | min="1"; server: > 0 |
| `refdoc_*` | ทุก eval | No | file | — | 5MB | — | pdf/jpg/png | ❌ ไม่มี size check | server: filesize <= 5MB; finfo MIME check |
| `sc_id` | ทุก form | Required | int | — | — | — | hidden | ✅ canAccessSchool | เพิ่ม (int) cast ทุกจุด |

---

## ส่วนที่ 2: จุดเสี่ยงที่อาจทำให้ระบบ Error

### 🔴 Critical Risk

| จุดเสี่ยง | เหตุผล | ผลที่อาจเกิด |
|-----------|--------|-------------|
| **Division by zero ใน ScoreService** | ถ้า `stu_sum=0` แต่มี hilltrib → `(hilltrib/stu_sum)*100` | PHP Warning หรือ INF% |
| **ไม่มี server-side MIME check บน upload** | accept attr เป็น client-only; bypass ได้ | RCE ถ้า PHP file ถูก execute |
| **Island citeria01 ไม่มี required** | ผู้ใช้ไม่เลือก → island_type=0 โดยไม่รู้ตัว | โรงเรียนเกาะไม่ผ่านการรับรอง |
| **Confirm opened ไม่มี required** | NULL ใน DB → ตีความผิดพลาด | ข้อมูลสถานะโรงเรียนผิดพลาด |
| **XSS ถ้า View::e() ไม่ครอบคลุม** | ช่องที่ render โดยไม่ escape | Script injection ในระบบ |

### 🟠 High Risk

| จุดเสี่ยง | เหตุผล | ผลที่อาจเกิด |
|-----------|--------|-------------|
| **Double submit ปุ่มบันทึก** | ไม่มี JS disable หลัง click | highland hilltrib อาจ insert ซ้ำ |
| **POST-without-PRG** | Refresh หลัง save → resubmit | ข้อมูล duplicate หรือ file upload ซ้ำ |
| **AJAX race condition (dropdown)** | เปลี่ยน SAO เร็วๆ | แสดงโรงเรียนของ SAO ผิด |
| **Multi-tab concurrent edit** | แก้ข้อมูลเดียวกัน 2 แท็บ | Last-write-wins; ข้อมูลสูญหาย |
| **Session CSRF expire** | ทิ้งฟอร์มนาน | 403 โดยไม่มี friendly message |
| **File upload directory permission** | writable check ขาด | 500 error ถ้า uploads/ ไม่มี permission |
| **ไม่มี bruteforce protection** | Login ไม่มี rate limit | Bot สามารถ brute force ได้ |

### 🟡 Medium Risk

| จุดเสี่ยง | เหตุผล | ผลที่อาจเกิด |
|-----------|--------|-------------|
| **ไม่มี maxlength บน text fields** | Column VARCHAR(255) ตัดข้อมูล | Data loss หรือ DB error |
| **หน้า Confirm submit โดยไม่ validate** | กด Submit ข้อมูลไม่ครบ → lock ทันที | SAO ต้อง unlock ให้ทุกครั้ง |
| **ไม่มี autosave** | ผู้ใช้กรอกนานแล้ว session หมด | ข้อมูลทั้งหมดหาย |
| **Google Maps API key exposed** | Key อยู่ใน config.php | ถ้า key leak → API bill |
| **utf8 vs utf8mb4** | Emoji 4-byte break MySQL utf8 | 500 error หรือ data truncate |

---

## ส่วนที่ 3: ข้อเสนอแนะเพื่อทำให้ระบบแข็งแรงขึ้น

### Frontend (JavaScript / HTML)

```
1. Disable ปุ่มบันทึกหลัง click แรก + แสดง spinner
   btn.addEventListener('click', () => { btn.disabled = true; btn.textContent = 'กำลังบันทึก...'; })

2. beforeunload warning เมื่อมีข้อมูลที่ยังไม่บันทึก
   window.addEventListener('beforeunload', (e) => { if (formDirty) e.preventDefault(); })

3. Pre-validate file size บน client
   fileInput.addEventListener('change', () => { if (file.size > 5*1024*1024) alert('ไฟล์ใหญ่เกิน 5MB'); })

4. Real-time cross-field warning (ไม่ block)
   - hilltrib total > stu_sum → warning
   - teacher count >> student count → warning
   - citeria01=0 (island) → modal warning "โรงเรียนนี้จะไม่ผ่านเกณฑ์เกาะ"

5. Required attributes เพิ่ม:
   - Island: citeria01 radio required
   - Confirm: opened radio required

6. min/max บน number fields:
   - stu_*: min="0" max="9999"
   - citeria041,05,06,07,08: min="0"
   - std_male/female: min="0" max="9999"

7. maxlength บน text fields:
   - sc_names, director_name, adresss: maxlength="255"
   - sao_comment, spt_comment: maxlength="1000"

8. AbortController บน AJAX dropdown เพื่อยกเลิก request เก่า
```

### Backend (PHP)

```
1. Post-Redirect-Get หลัง save สำเร็จ:
   header('Location: ' . App::url('highland/eval')); exit;

2. Server-side validation layer (เพิ่ม ValidationHelper class):
   - Type cast ทุก field ก่อนบันทึก
   - Range check: intval($val) >= 0
   - Enum check: in_array($status, [0,1,2])
   - Phone regex: /^\d{3}-\d{3}-\d{4}$/ (ถ้ามีค่า)

3. Division-by-zero guard ใน ScoreService:
   $pct = $stuSum > 0 ? round(($hilltrib / $stuSum) * 100, 2) : 0;

4. File upload security:
   $finfo = new finfo(FILEINFO_MIME_TYPE);
   $mime = $finfo->file($_FILES['file']['tmp_name']);
   if (!in_array($mime, ['application/pdf','image/jpeg','image/png'])) → reject
   $maxSize = 5 * 1024 * 1024; // 5MB
   if ($_FILES['file']['size'] > $maxSize) → reject

5. Session regeneration หลัง login:
   session_regenerate_id(true);

6. Rate limiting บน login:
   if (session_login_attempts >= 5) → block 15 นาที

7. Error handling: global exception handler
   set_exception_handler(function($e) {
     error_log($e->getMessage());
     if (!headers_sent()) http_response_code(500);
     require 'app/Views/errors/500.php';
   });

8. Required field validation ใน ConfirmController::school():
   if (!in_array((int)$_POST['opened'], [0, 1])) → error
   if ($opened === 0 && !in_array((int)$_POST['close_type'], [1,2,3])) → error
```

### Database

```
1. เพิ่ม constraints บน legacy tables (ใน migration):
   ALTER TABLE island_eval MODIFY citeria01 TINYINT(1) DEFAULT NULL COMMENT 'Gate: 1=island';
   
2. Charset utf8mb4:
   ALTER TABLE highland_eval CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
3. เพิ่ม indexes บน (sc_id, acadyears) ถ้ายังไม่มี:
   CREATE UNIQUE INDEX idx_highland_eval ON highland_eval (sc_id, acadyears);
   
4. Column type สำหรับ comment fields:
   ALTER TABLE highland_eval MODIFY sao_comment TEXT;
   ALTER TABLE island_eval MODIFY sao_comment TEXT;
```

### UX Improvements

```
1. Progress indicator บน multi-tab form:
   "Tab 2/10 เสร็จแล้ว | Tab 3 ยังไม่ครบ"

2. Autosave draft ทุก 5 นาที (localStorage):
   setInterval(() => { localStorage.setItem('draft_highland', JSON.stringify(formData)); }, 300000);

3. Confirmation dialog ก่อน action critical:
   - ก่อน Submit: "เมื่อส่งแล้วจะแก้ไขไม่ได้ ยืนยันหรือไม่?"
   - ก่อนลบ ethnic group: "ยืนยันลบ [ชนเผ่า]?"
   - ก่อน unlock: "ยืนยันให้โรงเรียนแก้ไขใหม่?"

4. Toast notification แทน alert():
   - บันทึกสำเร็จ: toast เขียว 3 วินาที
   - บันทึกล้มเหลว: toast แดงพร้อมเหตุผล
   - Warning: toast เหลืองแจ้ง cross-field issues

5. Field-level error messages (inline ใต้ช่อง):
   <div class="text-red-500 text-sm">กรุณากรอกจำนวนนักเรียน (ต้องไม่ติดลบ)</div>

6. Input mask บนเบอร์โทร:
   pattern="___-___-____" พร้อม auto-fill hyphen

7. Loading indicator บน AJAX:
   - Map pin save: "กำลังบันทึกพิกัด..."
   - Ethnic add: "กำลังเพิ่มกลุ่มชาติพันธุ์..."
```

### Testing

```
1. Unit Tests:
   - ScoreService: ทดสอบ edge cases: stu_sum=0, hilltrib>stu_sum, citeria01=null
   - IslandScoreService: citeria01=0 gate
   - Validation helpers: phone regex, MIME check, range check

2. Integration Tests (ใช้ sentinel sc_id=999999001):
   - Double submit → ข้อมูลไม่ duplicate
   - File upload → path correct, MIME reject .php
   - Multi-tab conflict detection

3. E2E Tests (Playwright/Selenium):
   - Happy path: login → select → eval → save → print
   - Error path: submit ไม่ครบ → error message ถูก field
   - Auth: school A ไม่เห็น school B

4. Security scan:
   - OWASP ZAP automated scan บน staging
   - Manual XSS test บนทุก text field
   - SQL injection test บน query params
```

---

*เอกสารนี้สร้างจากการวิเคราะห์ source code โดยตรง (app/Controllers/, app/Models/, app/Views/) รุ่น 2026-06-16*  
*ระดับความรุนแรง: Critical = ข้อมูลเสียหาย/security breach, High = ฟีเจอร์หลักผิดพลาด, Medium = UX แย่, Low = cosmetic*
