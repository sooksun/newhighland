# PRD — UX/UI Redesign (Presentation Layer Only)
## ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ (newhighland) — ปีงบประมาณ 2569

> เอกสารนี้เป็น **brief ส่งต่อให้ทีม/เอเจนต์ออกแบบ (claude design)** เพื่อยกเครื่องหน้าตา (UX/UI) ของระบบให้สวยงาม ทันสมัย และมีประสิทธิภาพสูง
> **ขอบเขตเด็ดขาด: แตะได้เฉพาะ "ชั้นนำเสนอ" (markup styling / CSS / theme tokens / icon / logo) เท่านั้น — ห้ามแตะ skeleton, business logic และ database**
> เอกสารฉบับฟังก์ชัน/ลอจิกอยู่ที่ [`docs/PRD.md`](PRD.md) และ [`docs/PLAN.md`](PLAN.md) — เอกสารนี้ไม่ทับซ้อน เป็นเลเยอร์ดีไซน์ที่วางทับโครงเดิม

---

## 0. TL;DR สำหรับนักออกแบบ

ระบบราชการ (สพฐ.) ให้โรงเรียน / สำนักงานเขต / ส่วนกลาง ใช้ประเมินและรับรองสถานะ "โรงเรียนพื้นที่ลักษณะพิเศษ" (พื้นที่สูง / เกาะ) ผ่าน 4 กระบวนการ ปัจจุบันใช้ Bootstrap 5 เปล่า ๆ ไม่มีแบรนด์ ไม่มีไอคอน ไม่มีอัตลักษณ์ ผลที่ต้องการ:

1. **ธีม + อัตลักษณ์** — โทนสีราชการที่ดูน่าเชื่อถือแต่ทันสมัย, โลโก้/favicon, ระบบไอคอน, typography ที่อ่านภาษาไทยสบายตา
2. **ยกระดับ component** — navbar, การ์ด dashboard, ฟอร์มหลายแท็บ 16 ข้อ, ตาราง, stepper, แผนที่, หน้า login, หน้า error ให้ดู polished, มี hierarchy, มี feedback ที่ชัด
3. **ประสิทธิภาพสูง** — โหลดเร็ว, ไม่เพิ่ม dependency หนัก ๆ, self-host ของสำคัญ, ไม่ทำ layout shift
4. **เข้าถึงง่าย** — WCAG AA, คอนทราสต์ผ่าน, ใช้คีย์บอร์ดได้, รองรับมือถือ/แท็บเล็ตของ จนท. ภาคสนาม

**กฎเหล็ก:** ทุกการเปลี่ยนแปลงต้องไม่ทำให้ route, ชื่อ field, id ที่ JS อ้าง, สัญญา view variables, สูตรคะแนน, และรูปแบบ PDF เปลี่ยนพฤติกรรม

---

## 1. สถาปัตยกรรมปัจจุบัน (สิ่งที่ต้องเคารพ)

PHP MVC เขียนเอง (ไม่มี framework หนัก) — front controller ที่ [`index.php`](../index.php)

```
app/
├── Core/        App, Router, View, Auth, Csrf, Db, Flash, Request, Upload   ← ห้ามแตะ
├── Controllers/ Auth, Home, School, Map, HighlandEval, Island, Confirm      ← ห้ามแตะ
├── Services/    ScoreService, IslandScoreService, PdfService, SchoolContext ← ห้ามแตะ
├── Models/                                                                   ← ห้ามแตะ
└── Views/       *.php  ← แก้ได้เฉพาะ "มาร์กอัป/คลาส/โครง HTML เพื่อความสวยงาม"
assets/css/app.css   ← ขยายได้เต็มที่ (เป็นบ้านหลักของงานดีไซน์)
images/              ← เพิ่ม logo / favicon / illustration ได้
```

**UI ปัจจุบัน:** Bootstrap 5.3.3 (CDN) + ฟอนต์ Sarabun (Google Fonts) + [`assets/css/app.css`](../assets/css/app.css) ที่มีแค่ ~10 บรรทัด (ตั้งฟอนต์ + border-radius การ์ด) ยังไม่มีโลโก้/favicon/ไอคอน/โทนแบรนด์

---

## 2. ขอบเขต — ทำอะไรได้ / ห้ามทำอะไร

### ✅ อนุญาต (Presentation layer)
- เพิ่ม/ปรับ CSS ทั้งหมดใน `assets/css/app.css` (และเพิ่มไฟล์ CSS ใหม่ได้)
- ปรับ **โครง HTML / class / โครง markup** ในไฟล์ `app/Views/**` เพื่อความสวยงาม (เช่น ห่อ div, เพิ่ม wrapper, เปลี่ยน utility class, ใส่ไอคอน, จัด grid ใหม่)
- เพิ่ม **logo, favicon, illustration, ไอคอนเซ็ต, web-font**
- ปรับ `layout.php`, `auth.php` (layout หลัก) ให้มี header/footer/branding ที่สวยขึ้น
- เพิ่ม micro-interaction เบา ๆ ด้วย CSS (transition, hover, focus, skeleton loading) และ JS เฉพาะที่เป็น progressive enhancement (เช่น sidebar toggle) — โดยไม่ชนกับ JS เดิม
- เพิ่ม design tokens ผ่าน CSS custom properties

### ⛔ ห้ามเด็ดขาด
1. **ห้ามแก้ route / URL** ใน `index.php` และห้ามเปลี่ยน `action=` ของฟอร์ม / endpoint ของ `fetch()`
2. **ห้ามเปลี่ยนชื่อ attribute `name=`** ของ input/select ทุกตัว (เช่น `score02`, `citeria041`, `sao_id`, `sc_id`, `username`, `password`, `director_tel` …) — ฝั่งเซิร์ฟเวอร์อ่านตามชื่อนี้ตรง ๆ
3. **ห้ามเปลี่ยน/ลบ `id=` และ class ที่ JavaScript อ้างถึง** เช่น `#sao`, `#school`, `#map`, `#pac-input`, `#sumScore`, `#stu_sum_view`, `#hilltribTable`, `#hillTotal`, `#newEthnic`, `#addEthnicBtn`, `#evalTabs`, `.tel-fmt`, `.stu`, `data-bs-target="#tab-*"` — เปลี่ยนได้แค่ "หน้าตา" ของมัน ไม่ใช่ตัวระบุ
4. **ห้ามแตะ** `Csrf::field()`, `Csrf::token()`, meta `csrf-token`, การ loop `Flash::pull()`, การเรียก `App::url()`/`App::asset()`/`View::e()` — ย้ายตำแหน่งได้ ห้ามลบ
5. **ห้ามแตะสูตรคะแนน** หรือไฟล์ใน `Services/` / `Core/` / `Controllers/` / `Models/`
6. **ห้ามแก้ database / schema / query**
7. **ห้ามเปลี่ยนรูปแบบ PDF** — view `highland/print.php` และ `island/print.php` ถูกใช้/วางทับลงเทมเพลต PDF (mPDF/FPDF overlay) สไตล์หน้าจอ HTML ของ print ปรับได้เฉพาะส่วนพรีวิวบนเว็บ **แต่ต้องไม่กระทบไฟล์ PDF ที่ออกจาก `PdfService`** → ถ้าไม่แน่ใจ ให้คงไว้
8. **ห้ามทำ regression** — ทุกหน้ายังต้อง submit/บันทึก/คำนวณ/พิมพ์ได้เหมือนเดิม

> หลักการ: ถ้าลบ CSS/markup ใหม่ออกหมด ระบบต้องยังทำงานได้ครบเหมือนก่อน redesign (graceful degradation)

---

## 3. ผู้ใช้และบริบท (Design Personas)

| Role | ใครใช้ | บริบทอุปกรณ์ | ความคาดหวัง |
|------|--------|--------------|-------------|
| `school` | ผอ./ครู ผู้กรอกข้อมูลโรงเรียน | มือถือ/แท็บเล็ตภาคสนาม + เดสก์ท็อป | ฟอร์มยาวต้องไม่น่ากลัว มี progress ชัด ปุ่มใหญ่กดง่าย |
| `sao` | จนท.สำนักงานเขต (สพป./สพม.) | เดสก์ท็อปเป็นหลัก | เห็นภาพรวมหลายโรงเรียน คัดกรอง/รับรองเร็ว |
| `admin` | ส่วนกลาง สพฐ. | เดสก์ท็อป | แดชบอร์ดสรุป ดูสถานะรวม รับรองขั้นสุดท้าย |

อายุผู้ใช้หลากหลาย ภาษาไทยล้วน ความน่าเชื่อถือแบบหน่วยงานราชการสำคัญกว่าความหวือหวา

---

## 4. Design Direction

### 4.1 อารมณ์ของแบรนด์
"ราชการยุคใหม่ที่ไว้ใจได้" — สะอาด โปร่ง เป็นระเบียบ มีความเป็นทางการแต่ไม่แข็งทื่อ สื่อถึงการศึกษา + พื้นที่ภูมิประเทศพิเศษ (ภูเขา/ทะเล-เกาะ)

### 4.2 Color System (เสนอ — ปรับได้ ขอให้คุมโทน)
ออกแบบเป็น **design tokens** ใน `:root` ของ `app.css` แล้ว map กับ Bootstrap CSS variables (`--bs-primary` ฯลฯ) เพื่อให้ utility เดิมรับธีมอัตโนมัติ

| Token | ค่าเสนอ | ใช้กับ |
|-------|---------|--------|
| `--brand-primary` | น้ำเงินเข้มเชิงราชการ (เช่น `#1e3a8a`/`#1d4ed8`) | navbar, ปุ่มหลัก, ลิงก์ |
| `--brand-secondary` | เขียวมรกต (เช่น `#0f766e`) | สถานะ "พร้อม/ผ่าน/รับรอง" |
| `--brand-accent-highland` | โทนภูเขา/น้ำตาลเขียว | โมดูลพื้นที่สูง |
| `--brand-accent-island` | โทนฟ้าทะเล | โมดูลพื้นที่เกาะ |
| `--state-success/warning/danger/info` | semantic | badge, alert, validation |
| `--surface / --surface-2 / --border / --text / --text-muted` | neutral scale | พื้นหลัง, การ์ด, เส้น |

- รองรับ **2 โมดูลด้วยสี accent ต่างกัน** (สูง vs เกาะ) เพื่อช่วยให้ผู้ใช้รู้ว่าอยู่บริบทไหน
- คอนทราสต์ตัวอักษร/พื้นหลังต้องผ่าน WCAG AA (≥ 4.5:1)
- (ทางเลือก) เตรียม dark-mode tokens ได้ แต่ default = light

### 4.3 Typography
- คงฟอนต์ **Sarabun** (เหมาะภาษาไทยราชการ) — แต่แนะนำ **self-host** (ลดพึ่ง Google Fonts ภายนอก, เร็วขึ้น, เลี่ยง FOIT) วางที่ `assets/fonts/`
- กำหนด type scale ชัดเจน (h1–h6, body, small, caption) + line-height ที่อ่านภาษาไทยสบาย (≥ 1.6 สำหรับ body)
- น้ำหนัก: 300/400/600/700 (มีอยู่แล้ว)

### 4.4 ระบบไอคอน
- เลือกชุดเดียวให้สม่ำเสมอ: **Bootstrap Icons** (เข้ากับ Bootstrap, เบา) หรือ **Lucide** — **self-host หรือใช้ SVG sprite** ไม่ดึงจาก CDN หนัก
- ใส่ไอคอนนำสายตาในเมนู, การ์ดกระบวนการ, ปุ่ม action, สถานะ, แท็บฟอร์ม
- ไอคอนต้องมี `aria-hidden` เมื่อเป็นเชิงตกแต่ง และมี label ข้อความกำกับ action เสมอ

### 4.5 โลโก้ / แบรนด์
- ออกแบบ **โลโก้/wordmark** ของระบบ (เช่น สัญลักษณ์ภูเขา+เกาะ+หนังสือ/ดาว) เป็น **SVG** + favicon (`.ico`/`.svg`/`apple-touch-icon`)
- วาง wordmark ใน navbar แทนข้อความ brand ปัจจุบัน ("ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ")
- คงข้อความ footer หน่วยงาน (สำนักนโยบายและแผนการศึกษาขั้นพื้นฐาน · สพฐ.) แต่จัดให้ดูเป็นทางการขึ้น (อาจใส่ตราหน่วยงานถ้ามีไฟล์)

### 4.6 Spacing / Radius / Elevation
- สเกลระยะห่างชัดเจน (4/8px base), การ์ด radius ~12–16px, เงา 2 ระดับ (resting / hover), เส้นขอบบาง 1px neutral
- เลี่ยงเงาฟุ้งเกินไป — ให้ดู crisp และเป็นทางการ

---

## 5. Component Spec (ของที่มีอยู่จริงในระบบ)

ต่อไปนี้คือ component จริงที่ปรากฏใน views — ออกแบบให้ครบทุกตัว:

| Component | ไฟล์อ้างอิง | สิ่งที่ต้อง redesign | ห้ามแตะ |
|-----------|------------|----------------------|---------|
| **App shell / navbar** | `layouts/layout.php` | โลโก้, เมนู, badge role (โรงเรียน/เขต/สพฐ.), ชื่อผู้ใช้, ปุ่มออกจากระบบ, footer | `App::url`, `Auth::role/name`, ลิงก์ logout |
| **Auth shell + Login** | `layouts/auth.php`, `auth/login.php` | หน้า login แบบ split/centered มีแบรนด์, ภาพประกอบภูเขา-เกาะ | field `username`/`password`, `Csrf::field`, `action` |
| **Flash messages** | `layouts/*` | toast/alert ที่สวย มีไอคอนตามชนิด (success/danger/info) | `Flash::pull()` + ชนิด type |
| **Dashboard + การ์ดสถิติ** | `home/dashboard.php` | stat card, การ์ด 4 กระบวนการ มีไอคอน+สี accident ตามโมดูล+badge สถานะ | `$stats[*]`, การกรองตาม role, `App::url` |
| **Stepper / breadcrumb** | `highland/select.php`, `highland/eval.php` | เปลี่ยน breadcrumb 4 ขั้น → **stepper แนวนอน** สวย ๆ บอก current/done/upcoming | ลำดับขั้น + ลิงก์ |
| **ฟอร์มเลือกสังกัด→โรงเรียน** | `highland/select.php`, `island/select.php` | select ที่สวย, loading state ของ dropdown โรงเรียน (async) | `#sao`,`#school`, `name=sao_id/sc_id`, fetch URL |
| **แบบประเมินหลายแท็บ (16 ข้อ)** | `highland/eval.php` | nav-pills → **tab/segmented control** สวย, การ์ดต่อแท็บ, sticky คะแนนรวม `#sumScore`, validation hint | `data-bs-toggle="pill"`, `#tab-*`, ทุก `name=`, `id=sumScore/stu_sum_view` |
| **ตารางกลุ่มชาติพันธุ์ (เพิ่ม/ลบแถว)** | `highland/eval.php` | ตารางสวย, ปุ่ม +เพิ่ม/ลบ มีไอคอน, แถวรวม | `#hilltribTable`,`#hillTotal`,`#newEthnic`,`#newEthnicNum`,`#addEthnicBtn` |
| **อัปโหลดเอกสารอ้างอิง (refdoc)** | `highland/eval.php` | dropzone/ปุ่มไฟล์ที่สวย แสดงไฟล์เดิม | input file `name`, `enctype` |
| **แท็บรับรอง สพท./สพฐ.** | `highland/eval.php` (`#tab-spt`) | ส่วนรับรองแบบ panel มีสถานะ | endpoint `eval/cert`, field ที่เกี่ยว |
| **แผนที่ปักหมุด + วัดความสูง** | `map/search.php`, `map/elevation.php` | topbar สวย, search box, ปุ่มยืนยัน, panel แสดงพิกัด/ความสูง | `#map`,`#pac-input`, Google Maps init, `CFG`, `saveUrl`, csrf |
| **หน้ารับรองคงอยู่ (ส่วน 3–4)** | `confirm/list.php`, `confirm/school.php` | ตาราง roster + ฟอร์มยืนยัน, สถานะ chip | endpoint `confirm/*`, field |
| **หน้า error** | `errors/403.php`, `errors/404.php` | empty-state สวย มีภาพประกอบ + ปุ่มกลับ | — |
| **Print preview** | `highland/print.php`, `island/print.php` | ปรับเฉพาะหน้าจอพรีวิว/ปุ่ม ไม่กระทบ PDF จริง | เนื้อหา/เลย์เอาต์ที่ feed PDF |

---

## 6. Per-Screen UX เป้าหมาย

1. **Login** — แบรนด์เด่น, ภาพประกอบสื่อภูเขา/เกาะ, ฟอร์มกลางจอ, ปุ่มเต็มกว้าง, error inline ที่ชัด
2. **Dashboard** — ทักทายตาม role, แถบสถิติ (มีพื้นที่ขยายเพิ่มการ์ดได้), 4 การ์ดกระบวนการแยกสี/ไอคอนชัด, badge "พร้อมใช้/อยู่ระหว่างพัฒนา"
3. **เลือกโรงเรียน** — stepper บนสุด, ฟอร์มกระชับ, dropdown async มี spinner/skeleton ระหว่างโหลด
4. **ปักหมุด (แผนที่)** — เต็มจอ, topbar แบรนด์, ช่องค้นหาเด่น, ปุ่ม "บันทึกตำแหน่ง → ถัดไป" ชัด, panel พิกัด/ความสูงอ่านง่าย
5. **แบบประเมิน 16 ข้อ** — แท็บ/segmented ชัด, แสดงความคืบหน้าการกรอกแต่ละกลุ่ม, **คะแนนรวมแบบ sticky** เห็นตลอด, ผ่าน/ไม่ผ่าน (≥50) มีสี+ไอคอน, ปุ่มบันทึก/พิมพ์เด่น
6. **รับรองคงอยู่** — ตาราง roster อ่านง่าย, สถานะ chip (ยืนยันแล้ว/รอ), ฟอร์มยืนยันแบบ modal/inline ที่ชัด
7. **Empty/Loading/Error states** — ออกแบบครบทุกหน้า (ไม่มีจอขาวเปล่า)

---

## 7. Non-Functional / ประสิทธิภาพ

- **Performance budget:** CSS รวม (ไม่รวม Bootstrap) ≤ ~50KB gzip; ไม่เพิ่ม JS framework; รูป/ภาพประกอบเป็น SVG หรือ optimized
- **Self-host** ฟอนต์ + ไอคอน + Bootstrap (พิจารณา) เพื่อลดการพึ่ง CDN ภายนอกและให้ใช้งานในเครือข่ายราชการที่อาจบล็อก CDN ได้ — `font-display: swap`, preconnect/preload ตามเหมาะสม
- **ไม่มี Cumulative Layout Shift** — กำหนดขนาดรูป/ฟอนต์ล่วงหน้า
- **Responsive** — mobile-first; navbar collapse ใช้ได้จริง; ฟอร์ม 16 ข้อใช้บนมือถือได้
- **Accessibility WCAG 2.1 AA** — focus ring ชัด, ลำดับ tab ถูก, label ครบ, สถานะสีต้องมีข้อความ/ไอคอนกำกับ (ไม่สื่อด้วยสีอย่างเดียว), aria สำหรับ tab/stepper/toast
- **Cross-browser** — Chrome/Edge/Firefox/Safari + WebView ราชการรุ่นเก่าพอควร
- **พิมพ์:** print stylesheet ของหน้าจอแยกจาก PDF engine — ระวังไม่กระทบไฟล์ที่ออกจาก mPDF/FPDF

---

## 8. แผนการนำส่ง (เสนอให้ทีมดีไซน์ทำเป็นเฟส)

1. **Foundations** — design tokens ใน `app.css` (สี/ฟอนต์/ระยะ/เงา/ไอคอน) + map กับ Bootstrap vars + self-host ฟอนต์/ไอคอน + favicon/logo
2. **App shell** — `layout.php` + `auth.php` (navbar, footer, flash, login) ← เห็นผลทั้งระบบทันที
3. **Dashboard** — stat + การ์ดกระบวนการ
4. **Flow พื้นที่สูง** — stepper, select, แผนที่, ฟอร์ม 16 ข้อ (หน้าหนักสุด)
5. **Flow เกาะ + รับรองคงอยู่** — ใช้ pattern เดียวกันจากข้อ 4 ให้สม่ำเสมอ
6. **Error/empty states + ขัดเงา + ตรวจ a11y/perf**

> หลังแต่ละเฟส: รัน smoke test (`php -S 127.0.0.1:8099 index.php`) ล็อกอินด้วยบัญชีทดสอบ (เขต `u6302`/`p@6302`, โรงเรียน `63020130`/`160271`) แล้วเดินครบ flow ว่า submit/คะแนน/พิมพ์ยังทำงาน

---

## 9. Definition of Done

- [ ] ทุกหน้ามีธีม/แบรนด์/ไอคอนสม่ำเสมอ มีโลโก้+favicon
- [ ] ไม่มี route/field-name/JS-id/endpoint ใดถูกเปลี่ยน (diff ตรวจได้)
- [ ] ครบทุก state: default / hover / focus / disabled / loading / error / empty
- [ ] Responsive ผ่านบนมือถือ-แท็บเล็ต-เดสก์ท็อป
- [ ] WCAG AA ผ่าน (คอนทราสต์ + คีย์บอร์ด + label)
- [ ] Performance budget ผ่าน, ไม่มี CLS, self-host ของสำคัญ
- [ ] Smoke test ครบ 4 ส่วน: ประเมินสูง / ประเมินเกาะ / รับรองสูง / รับรองเกาะ — บันทึก, คะแนน, **และ PDF ออกถูกต้องเหมือนเดิม**
- [ ] ลบ CSS/markup ใหม่ออกแล้วระบบยังทำงานได้ (no functional dependency on styling)

---

## 10. ภาคผนวก — รายการ selector ที่ JS/Server ผูกไว้ (ห้ามเปลี่ยนตัวระบุ)

> เปลี่ยนได้แค่รูปลักษณ์ ห้ามเปลี่ยน/ลบชื่อต่อไปนี้

- **Global:** `meta[name="csrf-token"]`, การวนลูป `Flash::pull()`, `App::url()`, `App::asset()`, `View::e()`
- **Login:** `name="username"`, `name="password"`, form `action=auth/login`
- **เลือกโรงเรียน:** `#sao`, `#school`, `name="sao_id"`, `name="sc_id"`, fetch `highland/schools` + header `X-Requested-With`
- **แผนที่:** `#map`, `#pac-input`, ตัวแปร `CFG` (scId/saveUrl/csrf), `google.maps.*`, endpoint `map/savelatlng`, `map/saveelevation`
- **ฟอร์ม 16 ข้อ:** `#evalTabs`, `data-bs-toggle="pill"`, `data-bs-target="#tab-general|students|ethnic|geo|transport|utility|stu|other|spt|…"`, `#sumScore`, `#stu_sum_view`, class `.stu`, class `.tel-fmt`, `#director_tel`, `#editor_tel`, ทุก `name="score##"`, `name="citeria##"`, `name="stu_*"`, `enctype="multipart/form-data"`
- **ตารางชาติพันธุ์:** `#hilltribTable`, `#hillTotal`, `#newEthnic`, `#newEthnicNum`, `#addEthnicBtn`, endpoint `highland/hilltrib/add|delete`
- **รับรอง:** endpoint `confirm/school|sao|spt`, `highland/eval/cert`, `island/eval/cert`
- **พิมพ์:** `highland/print`, `island/print` — เนื้อหาที่ feed PDF ห้ามเปลี่ยนโครง

---

*จัดทำเป็น brief สำหรับงาน redesign — อ้างอิงโครงและลอจิกฉบับเต็มที่ [`docs/PRD.md`](PRD.md)*
