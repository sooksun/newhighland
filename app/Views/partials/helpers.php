<?php
/**
 * View helpers (global namespace) — newhighland redesign
 * โหลดจาก app/bootstrap.php ; ใช้ใน template ได้ทันที
 *
 * nh_icon('home')        => <svg> ไอคอน stroke แบบ lucide
 * nh_brand_mark(38)      => โลโก้ ภูเขา+คลื่น ในโล่มน (SVG)
 *
 * ไอคอนพอร์ตจากดีไซน์ (js/icons.jsx) — ใช้ชุดเดียวกันทั้งระบบ
 */

if (!function_exists('nh_icon')) {

    /** ชุด path ภายใน viewBox 0 0 24 24 */
    function nh_icon_paths(): array
    {
        static $icons = null;
        if ($icons !== null) {
            return $icons;
        }
        return $icons = [
            'home'        => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/>',
            'dashboard'   => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
            'mountain'    => '<path d="M3 20 9.5 8l4 6 2.5-4L21 20Z"/>',
            'waves'       => '<path d="M2 7c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M2 13c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M2 19c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/>',
            'mapPin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'pinSolid'    => '<path d="M12 22s8-6 8-12a8 8 0 1 0-16 0c0 6 8 12 8 12Z"/>',
            'route'       => '<circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h6a4 4 0 0 0 0-8H9a4 4 0 0 1 0-8"/>',
            'fileText'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
            'clipboard'   => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 13 2 2 4-4"/>',
            'check'       => '<path d="m5 12 5 5L20 6"/>',
            'checkCircle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'x'           => '<path d="M6 6l12 12M18 6 6 18"/>',
            'alertCircle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
            'alertTri'    => '<path d="M10.3 4 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
            'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'chevDown'    => '<path d="m6 9 6 6 6-6"/>',
            'chevRight'   => '<path d="m9 6 6 6-6 6"/>',
            'chevLeft'    => '<path d="m15 6-6 6 6 6"/>',
            'arrowRight'  => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
            'login'       => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
            'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users'       => '<circle cx="9" cy="8" r="3.5"/><path d="M3 21a6 6 0 0 1 12 0"/><path d="M16 5.2a3.5 3.5 0 0 1 0 6.6M21 21a6 6 0 0 0-4-5.7"/>',
            'building'    => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h.01M15 16h.01"/>',
            'school'      => '<path d="m4 9 8-5 8 5v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/><path d="M9 21v-6h6v6"/>',
            'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'key'         => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3"/><path d="m17 6 2 2"/><path d="m15 8 1.5 1.5"/>',
            'plus'        => '<path d="M12 5v14M5 12h14"/>',
            'trash'       => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
            'upload'      => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
            'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19"/>',
            'moon'        => '<path d="M21 13A9 9 0 0 1 11 3a7 7 0 1 0 10 10Z"/>',
            'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'droplet'     => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"/>',
            'zap'         => '<path d="M13 3 4 14h6l-1 7 9-11h-6Z"/>',
            'phone'       => '<path d="M5 4h3l2 5-2.5 1.5a12 12 0 0 0 5 5L17 13l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
            'wifi'        => '<path d="M2 8.5a16 16 0 0 1 20 0"/><path d="M5 12a11 11 0 0 1 14 0"/><path d="M8.5 15.5a6 6 0 0 1 7 0"/><path d="M12 19h.01"/>',
            'ruler'       => '<path d="M3 9 9 3l12 12-6 6Z"/><path d="m7 8 1.5 1.5M10 11l1.5 1.5M13 14l1.5 1.5"/>',
            'award'       => '<circle cx="12" cy="9" r="6"/><path d="m9 14-1.5 7L12 18l4.5 3L15 14"/>',
            'printer'     => '<path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M8 17h8v4H8z"/><path d="M17 12.5h.01"/>',
            'shieldCheck' => '<path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6Z"/><path d="m9 12 2 2 4-4"/>',
            'flag'        => '<path d="M5 21V4"/><path d="M5 4h11l-2 3 2 3H5"/>',
            'locate'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
            'calendar'    => '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 9h16M9 3v4M15 3v4"/>',
            'eye'         => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'edit'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'list'        => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
            'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'refresh'     => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
            'trendUp'     => '<path d="m3 17 6-6 4 4 8-8"/><path d="M17 7h4v4"/>',
            'layers'      => '<path d="m12 3 9 5-9 5-9-5Z"/><path d="m3 13 9 5 9-5"/>',
            'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/>',
        ];
    }

    /**
     * คืน SVG ไอคอน (stroke แบบ lucide) — decorative โดยปริยาย (aria-hidden)
     * @param string $name  ชื่อไอคอนจาก nh_icon_paths()
     * @param int    $size  ขนาด px
     * @param string $class คลาส CSS เพิ่มเติม
     */
    function nh_icon(string $name, int $size = 20, string $class = '', float $stroke = 2.0): string
    {
        $paths = nh_icon_paths();
        if (!isset($paths[$name])) {
            return '';
        }
        $solid = ($name === 'pinSolid');
        $fill  = $solid ? 'currentColor' : 'none';
        $strk  = $solid ? 'none' : 'currentColor';
        $cls   = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '';
        return '<svg' . $cls . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24"'
            . ' fill="' . $fill . '" stroke="' . $strk . '" stroke-width="' . $stroke . '"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $paths[$name] . '</svg>';
    }

    /** render stepper จากชุดขั้นตอน $steps = [[id,label,sub,icon], ...] */
    function nh_render_stepper(array $steps, string $current): string
    {
        $idx = 0;
        foreach ($steps as $i => $s) {
            if ($s[0] === $current) { $idx = $i; break; }
        }
        $h = '<div class="stepper" role="list" aria-label="ขั้นตอนการประเมิน">';
        foreach ($steps as $i => $s) {
            $state = $i < $idx ? 'done' : ($i === $idx ? 'active' : '');
            $dot = $i < $idx ? nh_icon('check', 17) : nh_icon($s[3], 17);
            $aria = $i === $idx ? ' aria-current="step"' : '';
            $h .= '<div class="step ' . $state . '" role="listitem"' . $aria . '>'
                . '<span class="st-dot">' . $dot . '</span>'
                . '<span class="st-lbl"><b>' . htmlspecialchars($s[1], ENT_QUOTES, 'UTF-8') . '</b>'
                . '<span>' . htmlspecialchars($s[2], ENT_QUOTES, 'UTF-8') . '</span></span></div>';
            if ($i < count($steps) - 1) {
                $h .= '<span class="step-line' . ($i < $idx ? ' done' : '') . '"></span>';
            }
        }
        return $h . '</div>';
    }

    /**
     * Stepper ประเมินพื้นที่สูง (select → map → elev → eval → print → cert)
     * @param string $current id ของขั้นปัจจุบัน
     */
    function nh_stepper(string $current): string
    {
        return nh_render_stepper([
            ['select', 'เลือกโรงเรียน',     'สังกัด · โรงเรียน',       'school'],
            ['map',    'ปักหมุด',           'พิกัด · ความสูง ณ จุด',   'mapPin'],
            ['elev',   'วัดความสูง/ระยะ',    'เส้นทาง · ภูเขา/ราบ',     'ruler'],
            ['eval',   'แบบประเมิน 16 ข้อ',  'กรอก · คิดคะแนน',         'clipboard'],
            ['print',  'พิมพ์ผล',           'ออกเอกสาร PDF',           'printer'],
            ['cert',   'รับรองโดย สพท.',     'ยืนยันข้อมูลถูกต้อง โดย สพป./สพม.', 'shieldCheck'],
        ], $current);
    }

    /**
     * Stepper ประเมินพื้นที่เกาะ (select → eval → print → cert) — 4 ขั้น ไม่มีปักหมุด/วัดความสูง
     * @param string $current id ของขั้นปัจจุบัน
     */
    function nh_island_stepper(string $current): string
    {
        return nh_render_stepper([
            ['select', 'เลือกโรงเรียน',     'สังกัด · โรงเรียน',       'school'],
            ['eval',   'แบบประเมิน 15 ข้อ',  'กรอก · คิดคะแนน',         'clipboard'],
            ['print',  'พิมพ์ผล',           'ออกเอกสาร PDF',           'printer'],
            ['cert',   'รับรองโดย สพท.',     'ยืนยันข้อมูลถูกต้อง โดย สพป./สพม.', 'shieldCheck'],
        ], $current);
    }

    /**
     * Breadcrumb navigator — สร้างอัตโนมัติจาก path ปัจจุบัน
     * คืน '' สำหรับหน้าแดชบอร์ด/หน้าที่ไม่อยู่ในแผนผัง (จะไม่แสดงแถบนำทาง)
     *
     * @param string $curPath path ปัจจุบัน (ไม่มี base path, ไม่มี / นำหน้า) เช่น 'highland/eval'
     */
    function nh_breadcrumb(string $curPath): string
    {
        // จุดเริ่ม (แดชบอร์ด) ใช้ร่วมทุกสาขา
        $home = ['แดชบอร์ด', 'dashboard'];
        // แต่ละ trail = ลำดับ [label, path|null]  (null = ขั้นปัจจุบัน ไม่ลิงก์)
        $trails = [
            'report'         => [$home, ['รายงานสถิติ', null]],
            'highland'       => [$home, ['ประเมินพื้นที่สูง', 'highland'], ['เลือกโรงเรียน', null]],
            'map'            => [$home, ['ประเมินพื้นที่สูง', 'highland'], ['ปักหมุด · วัดความสูง', null]],
            'highland/eval'  => [$home, ['ประเมินพื้นที่สูง', 'highland'], ['แบบประเมิน 16 ข้อ', null]],
            'highland/print' => [$home, ['ประเมินพื้นที่สูง', 'highland'], ['พิมพ์ผล', null]],
            'highland/cert'  => [$home, ['รออนุมัติ (สพท.)', 'highland/cert'], ['พื้นที่สูง', null]],
            'island'         => [$home, ['ประเมินพื้นที่เกาะ', 'island'], ['เลือกโรงเรียน', null]],
            'island/eval'    => [$home, ['ประเมินพื้นที่เกาะ', 'island'], ['แบบประเมิน 15 ข้อ', null]],
            'island/print'   => [$home, ['ประเมินพื้นที่เกาะ', 'island'], ['พิมพ์ผล', null]],
            'island/cert'    => [$home, ['รออนุมัติ (สพท.)', 'highland/cert'], ['พื้นที่เกาะ', null]],
            'confirm'        => [$home, ['รับรองการคงอยู่', null]],
        ];

        // เลือก trail โดยจับคู่ key ที่ยาว/เฉพาะเจาะจงที่สุดก่อน (longest-prefix)
        $keys = array_keys($trails);
        usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a));
        $trail = null;
        foreach ($keys as $k) {
            if ($curPath === $k || str_starts_with($curPath, $k . '/')) {
                $trail = $trails[$k];
                break;
            }
        }
        if ($trail === null) {
            return '';   // dashboard เอง หรือหน้าอื่น — ไม่แสดง breadcrumb
        }

        $last = count($trail) - 1;
        $items = '';
        foreach ($trail as $i => [$label, $path]) {
            $lbl = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            if ($i === $last || $path === null) {
                $items .= '<li class="breadcrumb-item active" aria-current="page">' . $lbl . '</li>';
            } else {
                $href = htmlspecialchars(App::url($path), ENT_QUOTES);
                $ico  = $i === 0 ? nh_icon('home', 15) . ' ' : '';
                $items .= '<li class="breadcrumb-item"><a href="' . $href . '">' . $ico . $lbl . '</a></li>';
            }
        }
        return '<nav class="nh-breadcrumb" aria-label="เส้นทางนำทาง">'
            . '<ol class="breadcrumb">' . $items . '</ol></nav>';
    }

    /** โลโก้แบรนด์: รูป images/logo.png (สเกลตามความสูง รักษาสัดส่วน) */
    function nh_brand_mark(int $size = 38, string $class = ''): string
    {
        $src = App::url('images/logo.png');
        $cls = 'nh-logo' . ($class !== '' ? ' ' . $class : '');
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '"'
            . ' alt="โลโก้ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ"'
            . ' class="' . htmlspecialchars($cls, ENT_QUOTES) . '"'
            . ' style="height:' . $size . 'px;width:auto;display:block;" />';
    }
}
