<?php /** @var array $ctx; @var int $threshold; @var string $google_key */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
  <title>วัดความสูงและระยะทาง</title>
  <script>(function(){try{var t=localStorage.getItem('nh-theme')||'light';document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= App::asset('css/app.css') ?>" rel="stylesheet">
  <style>
    #map{height:46vh;width:100%;}
    #chart{height:260px;width:100%;}
  </style>
</head>
<body>
  <header class="nh-row between" style="padding:var(--s-3) var(--s-5);border-bottom:1px solid var(--border);background:var(--surface);gap:var(--s-4);">
    <div class="nh-row gap-3" style="min-width:0;">
      <?= nh_brand_mark(34) ?>
      <div style="min-width:0;">
        <span class="badge badge-highland"><?= nh_icon('ruler', 13) ?> ขั้นที่ 3 · วัดความสูง/ระยะทาง</span>
        <div class="muted" style="font-size:var(--fs-xs);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= View::e($ctx['sc_name']) ?> → ศาลากลางจังหวัด<?= View::e($ctx['province']) ?></div>
      </div>
    </div>
    <a href="<?= App::url('map/search?sc_id=' . $ctx['sc_id']) ?>" class="btn btn-sm btn-outline-secondary"><?= nh_icon('chevLeft', 16) ?> กลับไปปักหมุด</a>
  </header>

  <div id="status" class="alert alert-info rounded-0 mb-0 py-2 small">กำลังประมวลผลเส้นทางและความสูง...</div>
  <div id="map"></div>
  <div id="chart"></div>

  <div class="container my-3">
    <div id="result" class="card border-0 shadow-sm d-none">
      <div class="card-body">
        <h2 class="h6 fw-bold">ผลการตรวจสอบ</h2>
        <ul class="mb-3">
          <li>ระยะทางจากโรงเรียนถึงศาลากลางจังหวัด: <b><span id="r_dist">-</span></b> กิโลเมตร</li>
          <li>ความสูงที่สุดในเส้นทาง: <b><span id="r_high">-</span></b> เมตร (ที่ กม. <span id="r_at">-</span>)</li>
          <li>ความสูงเฉลี่ยของจังหวัด: <b><?= View::e(number_format($ctx['prov_high'], 0)) ?></b> เมตร · เกณฑ์ <?= View::e($threshold) ?> เมตร</li>
          <li>ผลประเมินภูมิศาสตร์: <b><span id="r_type" class="fs-5">-</span></b></li>
        </ul>
        <button id="saveBtn" class="btn btn-primary" onclick="saveResult()" disabled>บันทึกและดำเนินการต่อ →</button>
      </div>
    </div>
  </div>

  <script src="https://www.gstatic.com/charts/loader.js"></script>
  <script>
    const CFG = <?= json_encode([
        'scId'      => $ctx['sc_id'],
        'name'      => $ctx['sc_name'],
        'province'  => $ctx['province'],
        'lat0'      => $ctx['sc_lat'] !== '' ? (float)$ctx['sc_lat'] : 0,
        'lng0'      => $ctx['sc_lng'] !== '' ? (float)$ctx['sc_lng'] : 0,
        'lat2'      => $ctx['prov_lat'] !== '' ? (float)$ctx['prov_lat'] : 0,
        'lng2'      => $ctx['prov_lng'] !== '' ? (float)$ctx['prov_lng'] : 0,
        'high'      => $ctx['prov_high'],
        'threshold' => $threshold,
        'saveUrl'   => App::url('map/saveelevation'),
        'csrf'      => Csrf::token(),
    ], JSON_UNESCAPED_UNICODE) ?>;

    const SAMPLES = 256;
    let map, chartData = null, computed = null, carMarker = null, pathPts = [];

    // ไอคอนรถ (SVG วงกลมแดง) สำหรับเลื่อนตามเส้นทางเมื่อชี้/ลากบนกราฟ
    const CAR_SVG =
      "<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 24 24'>" +
      "<circle cx='12' cy='12' r='11' fill='#fff' stroke='#d32f2f' stroke-width='1.6'/>" +
      "<path fill='#d32f2f' d='M5.6 14.2 6.9 10c.3-.9 1-1.5 1.9-1.5h6.4c.9 0 1.6.6 1.9 1.5l1.3 4.2v3.3c0 .3-.2.5-.5.5h-1c-.3 0-.5-.2-.5-.5V17H7.6v.5c0 .3-.2.5-.5.5h-1c-.3 0-.5-.2-.5-.5v-3.3zm2-.7h8.8l-.9-2.8c-.1-.3-.3-.4-.5-.4H8.9c-.2 0-.4.1-.5.4l-.8 2.8zM8 16a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z'/>" +
      "</svg>";

    google.charts.load('current', {packages:['corechart']});

    function initMap() {
      map = new google.maps.Map(document.getElementById('map'), {
        zoom: 9, center: {lat: CFG.lat0, lng: CFG.lng0}, mapTypeId: 'terrain',
        streetViewControl: true,   // เปิด Street View (ลาก pegman ลงบนถนนเพื่อดูภาพถนน)
        // ปุ่มสลับมุมมอง: แผนที่ / ดาวเทียม / ผสม / ภูมิประเทศ
        mapTypeControl: true,
        mapTypeControlOptions: {
          style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
          mapTypeIds: ['roadmap', 'satellite', 'hybrid', 'terrain']
        }
      });
      if (!CFG.lat0 || !CFG.lat2) {
        setStatus('danger', 'ไม่พบพิกัดโรงเรียนหรือศาลากลางจังหวัด — กรุณาปักหมุดและตรวจข้อมูลจังหวัด');
        return;
      }
      const ds = new google.maps.DirectionsService();
      ds.route({
        origin: {lat: CFG.lat0, lng: CFG.lng0},
        destination: {lat: CFG.lat2, lng: CFG.lng2},
        travelMode: 'DRIVING'
      }, (resp, status) => {
        if (status !== 'OK') { setStatus('danger', 'ค้นหาเส้นทางไม่สำเร็จ: ' + status); return; }
        const path = resp.routes[0].overview_path;
        new google.maps.Polyline({path, strokeColor:'#0d6efd', map});
        const b = new google.maps.LatLngBounds();
        path.forEach(p => b.extend(p));
        map.fitBounds(b);
        const elev = new google.maps.ElevationService();
        elev.getElevationAlongPath({path, samples: SAMPLES}, plot);
      });
    }

    function plot(results, status) {
      if (status !== 'OK' || !results) { setStatus('danger', 'ดึงข้อมูลความสูงไม่สำเร็จ'); return; }
      const path = results.map(r => r.location);
      const distM = google.maps.geometry.spherical.computeLength(path);
      const distKm = (distM/1000) + (distM/1000)*0.0345;     // ค่าชดเชยเส้นทางจริง (ตามระบบเดิม)
      const inc = distM / results.length;

      let maxH = 0, atKm = 0;
      const rows = [['กม.', 'ความสูง (ม.)']];
      results.forEach((r, i) => {
        rows.push(['กม.' + Math.round(inc*i/1000), r.elevation]);
        if (r.elevation > maxH) { maxH = r.elevation; atKm = Math.round(inc*i/1000); }
      });

      const isHighland = (maxH > CFG.high) || (maxH > CFG.threshold);
      computed = {highest: Math.round(maxH), distance: +distKm.toFixed(2), isHighland};

      document.getElementById('r_dist').textContent = computed.distance.toFixed(2);
      document.getElementById('r_high').textContent = computed.highest;
      document.getElementById('r_at').textContent = atKm;
      const t = document.getElementById('r_type');
      t.textContent = isHighland ? 'ภูเขา / พื้นที่สูง' : 'พื้นราบ';
      t.className = 'fs-5 fw-bold ' + (isHighland ? 'text-success' : 'text-danger');

      document.getElementById('result').classList.remove('d-none');
      document.getElementById('saveBtn').disabled = false;
      setStatus('success', 'ประมวลผลเสร็จ — ชี้/ลากบนกราฟด้านล่างเพื่อเลื่อนรถตามเส้นทาง');

      // เก็บตำแหน่ง lat/lng ของแต่ละจุดตัวอย่าง (ดัชนีตรงกับแถวในกราฟ) + วางรถที่จุดเริ่มต้น
      pathPts = path;
      ensureCarMarker();
      if (pathPts.length) { carMarker.setPosition(pathPts[0]); carMarker.setVisible(true); }

      google.charts.setOnLoadCallback(() => {
        const chart = new google.visualization.ColumnChart(document.getElementById('chart'));
        chart.draw(google.visualization.arrayToDataTable(rows),
          {legend:'none', hAxis:{textPosition:'none'}, vAxis:{title:'ความสูง (ม.)'}});
        // ชี้/ลากบนแท่งกราฟ → เลื่อน car icon ไปยังจุดบนเส้นทางที่ตรงกัน
        google.visualization.events.addListener(chart, 'onmouseover', e => moveCarToRow(e.row));
      });
    }

    /** สร้าง car marker บนแผนที่ (ครั้งเดียว) */
    function ensureCarMarker() {
      if (carMarker || !map) return;
      carMarker = new google.maps.Marker({
        map,
        icon: {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(CAR_SVG),
          scaledSize: new google.maps.Size(40, 40),
          anchor: new google.maps.Point(20, 20),
        },
        zIndex: 9999,
        clickable: false,
        visible: false,
      });
    }

    /** เลื่อนรถไปยังจุดบนเส้นทางที่ตรงกับแถวกราฟ (row) */
    function moveCarToRow(row) {
      if (row == null || !carMarker) return;
      const pt = pathPts[row];
      if (!pt) return;
      carMarker.setPosition(pt);
      carMarker.setVisible(true);
    }

    function setStatus(type, msg) {
      const el = document.getElementById('status');
      el.className = 'alert alert-' + type + ' rounded-0 mb-0 py-2 small';
      el.textContent = msg;
    }

    async function saveResult() {
      if (!computed) return;
      const body = new URLSearchParams({
        _csrf: CFG.csrf, sc_id: CFG.scId, name: CFG.name,
        highest: computed.highest, distance: computed.distance,
        average_height: CFG.high, lat: CFG.lat0, lng: CFG.lng0
      });
      const res = await fetch(CFG.saveUrl, {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      if (data.ok) {
        if (!data.is_highland) alert('ผลการประเมิน: พื้นราบ — ไม่จัดเป็นโรงเรียนพื้นที่สูง จบการประเมิน');
        window.location.href = data.next;
      } else alert('บันทึกไม่สำเร็จ');
    }
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=<?= View::e($google_key) ?>&libraries=geometry&callback=initMap"></script>
</body>
</html>
