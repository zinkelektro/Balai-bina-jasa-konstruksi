(function () {
  var K = window.KontenShared;

  function cardHTML(kat) {
    var t = K.temaOf(kat.tema);
    var params = (kat.param || []).map(function (p) {
      return '<div class="param-item"><div class="param-dot" style="background:' + t.dot + '"></div>' +
        '<div><div class="param-text">' + K.esc(p.teks) + '</div><div class="param-std">' + K.esc(p.standar) + '</div></div></div>';
    }).join('');
    var waktu = (kat.waktu || []).map(function (w) {
      return '<div class="turnaround-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' + K.esc(w) + '</div>';
    }).join('');
    return '<div class="material-card">' +
      '<div class="material-card-head"><div class="material-icon" style="background:' + t.bg + ';color:' + t.fg + '">' + K.svgIcon(kat.ikon) + '</div>' +
      '<div class="material-title"><h3>' + K.esc(kat.judul) + '</h3><p>' + K.esc(kat.subjudul) + '</p></div></div>' +
      '<div class="material-body"><div class="param-grid">' + params + '</div>' +
      (waktu ? '<div class="turnaround">' + waktu + '</div>' : '') +
      '</div></div>';
  }

  function render(data) {
    var wrap = document.getElementById('pengujianList');
    if (!wrap || !data || !data.kategori) return;
    wrap.innerHTML = data.kategori.map(cardHTML).join('');
  }

  fetch('konten-api.php?action=get&page=pengujian')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () { /* biarkan konten statis bawaan tampil */ });
})();
