<?php /** @var array $ctx; @var string $google_key */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
  <title>ปักหมุดตำแหน่งโรงเรียน</title>
  <script>(function(){try{var t=localStorage.getItem('nh-theme')||'light';document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= App::asset('css/app.css') ?>" rel="stylesheet">
  <style>
    html,body{height:100%;margin:0;}
    .map-topbar{height:64px;}
    #map{height:calc(100vh - 64px);width:100%;}
    #pac-input{margin:10px;width:min(520px,80%);}
  </style>
</head>
<body>
  <header class="map-topbar nh-row between" style="padding:0 var(--s-5);border-bottom:1px solid var(--border);background:var(--surface);gap:var(--s-4);">
    <div class="nh-row gap-3" style="min-width:0;">
      <?= nh_brand_mark(34) ?>
      <div style="min-width:0;">
        <div class="nh-row gap-2 wrap">
          <span class="badge badge-highland"><?= nh_icon('mapPin', 13) ?> ขั้นที่ 2 · ปักหมุดที่ตั้ง</span>
        </div>
        <div style="font-family:var(--font-head);font-weight:600;font-size:var(--fs-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= View::e($ctx['sc_name']) ?></div>
      </div>
    </div>
    <a href="<?= App::url('highland') ?>" class="btn btn-sm btn-outline-secondary"><?= nh_icon('x', 16) ?> ยกเลิก</a>
  </header>
  <input id="pac-input" class="form-control" type="text" placeholder="พิมพ์ชื่อสถานที่เพื่อค้นหา...">
  <div id="map"></div>

  <script>
    const CFG = <?= json_encode([
        'scId'    => $ctx['sc_id'],
        'name'    => $ctx['sc_name'],
        'lat0'    => $ctx['sc_lat'] !== '' ? (float)$ctx['sc_lat'] : ($ctx['prov_lat'] !== '' ? (float)$ctx['prov_lat'] : 19.0),
        'lng0'    => $ctx['sc_lng'] !== '' ? (float)$ctx['sc_lng'] : ($ctx['prov_lng'] !== '' ? (float)$ctx['prov_lng'] : 99.0),
        'saveUrl' => App::url('map/savelatlng'),
        'csrf'    => Csrf::token(),
    ], JSON_UNESCAPED_UNICODE) ?>;

    let map, infowindow, elevator;

    function initMap() {
      const center = {lat: CFG.lat0, lng: CFG.lng0};
      map = new google.maps.Map(document.getElementById('map'), {
        center, zoom: 16, mapTypeId: 'satellite'
      });
      elevator = new google.maps.ElevationService();
      infowindow = new google.maps.InfoWindow();

      // search box
      const input = document.getElementById('pac-input');
      const searchBox = new google.maps.places.SearchBox(input);
      map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
      map.addListener('bounds_changed', () => searchBox.setBounds(map.getBounds()));
      searchBox.addListener('places_changed', () => {
        const places = searchBox.getPlaces();
        if (!places.length) return;
        const b = new google.maps.LatLngBounds();
        places.forEach(p => { if (p.geometry) b.extend(p.geometry.location); });
        map.fitBounds(b);
      });

      // จุดเริ่มต้น
      showElevation(center);
      map.addListener('click', e => showElevation(e.latLng.toJSON ? e.latLng.toJSON() : e.latLng));
    }

    function showElevation(location) {
      elevator.getElevationForLocations({locations: [location]}, (results, status) => {
        const lat = (typeof location.lat === 'function') ? location.lat() : location.lat;
        const lng = (typeof location.lng === 'function') ? location.lng() : location.lng;
        let high = 0;
        if (status === 'OK' && results[0]) high = results[0].elevation;
        infowindow.setPosition({lat, lng});
        const html = `
          <div style="min-width:230px">
            <div class="text-center fw-bold mb-2">${CFG.name}</div>
            <div class="mb-1">ละติจูด: <input id="f_lat" class="form-control form-control-sm" value="${(+lat).toFixed(8)}"></div>
            <div class="mb-1">ลองจิจูด: <input id="f_lng" class="form-control form-control-sm" value="${(+lng).toFixed(8)}"></div>
            <div class="mb-2">ความสูง (ม.): <input id="f_high" class="form-control form-control-sm" value="${(+high).toFixed(2)}"></div>
            <button class="btn btn-sm btn-primary w-100" onclick="savePin()">บันทึกและตรวจสอบ →</button>
          </div>`;
        infowindow.setContent(html);
        infowindow.open(map);
      });
    }

    async function savePin() {
      const body = new URLSearchParams({
        _csrf: CFG.csrf, sc_id: CFG.scId,
        lat: document.getElementById('f_lat').value,
        lng: document.getElementById('f_lng').value,
        location_high: document.getElementById('f_high').value
      });
      const res = await fetch(CFG.saveUrl, {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      if (data.ok) { window.location.href = data.next; }
      else alert('บันทึกไม่สำเร็จ');
    }
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=<?= View::e($google_key) ?>&libraries=places&callback=initMap"></script>
</body>
</html>
