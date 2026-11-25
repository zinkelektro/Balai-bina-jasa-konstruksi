(function () {
  var K = window.KontenShared;

  function statHTML(s) {
    return '<div><div class="stat-num">' + K.esc(s.angka) + '</div><div class="stat-label">' + K.esc(s.label) + '</div></div>';
  }

  function unitHTML(u) {
    var t = K.temaOf(u.tema);
    var items = (u.items || []).map(function (i) { return '<li>' + K.esc(i) + '</li>'; }).join('');
    return '<div class="lab-card"><div class="lab-card-icon" style="background:' + t.bg + ';color:' + t.fg + '">' + K.svgIcon(u.ikon) + '</div>' +
      '<h3>' + K.esc(u.judul) + '</h3><p>' + K.esc(u.desc) + '</p><ul>' + items + '</ul></div>';
  }

  function render(data) {
    var statWrap = document.getElementById('labStatWrap');
    if (statWrap && data.stats) statWrap.innerHTML = data.stats.map(statHTML).join('');
    var unitWrap = document.getElementById('labUnitWrap');
    if (unitWrap && data.unit) unitWrap.innerHTML = data.unit.map(unitHTML).join('');
    if (data.akreditasi) {
      var judul = document.getElementById('labAkredJudul');
      var desc = document.getElementById('labAkredDesc');
      if (judul) judul.textContent = data.akreditasi.judul;
      if (desc) desc.textContent = data.akreditasi.desc;
    }
  }

  fetch('konten-api.php?action=get&page=laboratorium')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () {});
})();
