/* render-beranda.js
 * Mengisi bagian "Jenis Layanan" di index.html dari konten-api.php?page=beranda
 * (diedit admin/editor lewat admin.html > Konten Website > Beranda).
 * Jika data gagal dimuat, kartu statis bawaan di index.html tetap tampil (fallback).
 */
(function () {
  var K = window.KontenShared;
  var grid = document.getElementById('layananGrid');
  if (!K || !grid) return;

  var ARROW = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';

  function cardHTML(l, i) {
    var t = K.temaOf(l.tema);
    return '<a href="#layanan" class="layanan-card" data-idx="' + i + '">' +
      '<div class="layanan-icon-wrap" style="background:' + t.bg + ';color:' + t.fg + '">' + K.svgIcon(l.ikon, 26) + '</div>' +
      '<div class="layanan-title">' + K.esc(l.judul) + '</div>' +
      '<div class="layanan-desc">' + K.esc(l.desc) + '</div>' +
      '<div class="layanan-arrow">' + K.esc(l.tombol || 'Ajukan sekarang') + ' ' + ARROW + '</div></a>';
  }

  function setText(id, val) {
    var n = document.getElementById(id);
    if (n && typeof val === 'string' && val.trim() !== '') n.textContent = val;
  }

  function render(data) {
    if (data.header) {
      setText('layananEyebrow', data.header.eyebrow);
      setText('layananTitle', data.header.judul);
      setText('layananSub', data.header.sub);
    }
    if (!Array.isArray(data.layanan)) return;
    var list = data.layanan.filter(function (l) { return l && l.judul; });

    if (!list.length) {
      grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-sub)">Belum ada jenis layanan yang ditampilkan.</p>';
    } else {
      grid.innerHTML = list.map(cardHTML).join('');
      Array.prototype.forEach.call(grid.querySelectorAll('.layanan-card'), function (a) {
        a.addEventListener('click', function (e) {
          var l = list[parseInt(a.getAttribute('data-idx'), 10)];
          if (typeof window.bukaFormLayanan === 'function') window.bukaFormLayanan(e, l.judul);
        });
      });
    }

    // Dropdown "Jenis Layanan" pada formulir permohonan ikut sinkron
    var sel = document.getElementById('jenisLayananSelect');
    if (sel) {
      var cur = sel.value;
      sel.innerHTML = '<option value="">\u2014 Pilih jenis layanan \u2014</option>' +
        list.map(function (l) { return '<option>' + K.esc(l.judul) + '</option>'; }).join('');
      if (window.__jenisTertunda) sel.value = window.__jenisTertunda;
      else if (cur) sel.value = cur;
    }
  }

  fetch('konten-api.php?action=get&page=beranda', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () { /* fallback statis dipertahankan */ });
})();
