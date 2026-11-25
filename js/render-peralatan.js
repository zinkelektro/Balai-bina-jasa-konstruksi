(function () {
  var K = window.KontenShared;

  function statusBadge(status) {
    var s = (status || '').toLowerCase();
    var cls = 'status-ok';
    if (s.indexOf('kalibrasi') !== -1) cls = 'status-kalibrasi';
    else if (s.indexOf('rusak') !== -1 || s.indexOf('perbaikan') !== -1) cls = 'status-rusak';
    return '<span class="status-badge ' + cls + '">' + K.esc(status) + '</span>';
  }

  function kategoriHTML(kat) {
    var t = K.temaOf(kat.tema);
    var rows = (kat.alat || []).map(function (a) {
      return '<tr><td>' + K.esc(a.nama) + '</td><td>' + K.esc(a.merk) + '</td><td>' + K.esc(a.jumlah) + '</td><td>' + K.esc(a.kapasitas) + '</td><td>' + statusBadge(a.status) + '</td></tr>';
    }).join('');
    return '<div class="category-header"><span style="background:' + t.bg + ';color:' + t.fg + '">' + K.svgIcon(kat.ikon, 18) + '</span>' + K.esc(kat.judul) + '</div>' +
      '<div style="overflow-x:auto"><table class="alat-table">' +
      '<thead><tr><th>Nama Alat</th><th>Merk / Model</th><th>Jumlah</th><th>Kapasitas</th><th>Status</th></tr></thead>' +
      '<tbody>' + rows + '</tbody></table></div>';
  }

  function render(data) {
    var wrap = document.getElementById('peralatanList');
    if (!wrap || !data || !data.kategori) return;
    wrap.innerHTML = data.kategori.map(kategoriHTML).join('');
  }

  fetch('konten-api.php?action=get&page=peralatan')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () {});
})();
