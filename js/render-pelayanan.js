(function () {
  var K = window.KontenShared;

  function alurHTML(s, i) {
    return '<div class="alur-step-box"><div class="step-num">' + (i + 1) + '</div>' +
      '<div class="step-title">' + K.esc(s.judul) + '</div><div class="step-desc">' + K.esc(s.desc) + '</div></div>';
  }

  function layananHTML(l) {
    var t = K.temaOf(l.tema);
    return '<div class="service-card"><div class="service-icon" style="background:' + t.bg + ';color:' + t.fg + '">' + K.svgIcon(l.ikon, 22) + '</div>' +
      '<h3>' + K.esc(l.judul) + '</h3><p>' + K.esc(l.desc) + '</p>' +
      '<a href="#" onclick="return bukaFormLayananPage(event, encodeURIComponent(l.judul || ''))" class="service-link">' + K.esc(l.tautan || 'Ajukan') + ' &rarr; </a></div>';
  }

  function jamHTML(j) {
    return '<div class="jam-item"><h4>' + K.esc(j.judul) + '</h4>' +
      '<div class="jam-val">' + K.esc(j.nilai) + '</div><div class="jam-note">' + K.esc(j.catatan) + '</div></div>';
  }

  function render(data) {
    var alurWrap = document.getElementById('alurWrap');
    if (alurWrap && data.alur) alurWrap.innerHTML = data.alur.map(alurHTML).join('');
    var layananWrap = document.getElementById('layananWrap');
    if (layananWrap && data.layanan) layananWrap.innerHTML = data.layanan.map(layananHTML).join('');
    var jamWrap = document.getElementById('jamWrap');
    if (jamWrap && data.jam) jamWrap.innerHTML = data.jam.map(jamHTML).join('');
  }

  fetch('konten-api.php?action=get&page=pelayanan')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () {});
})();
