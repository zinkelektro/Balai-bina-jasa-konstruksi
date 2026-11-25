(function () {
  var K = window.KontenShared;

  var TINGKAT = [
    { key: 'pimpinan',    label: 'Pimpinan' },
    { key: 'kabid',       label: 'Kepala Bidang & Subbagian' },
    { key: 'kasi',        label: 'Kepala Seksi' },
    { key: 'koordinator', label: 'Koordinator Laboratorium' },
    { key: 'personel',    label: 'Personel Laboratorium' }
  ];

  // Teks tugas boleh berisi <br> dari data lama; diubah jadi baris baru lalu di-escape.
  function multiline(t) {
    return K.esc(String(t || '').replace(/<br\s*\/?>/gi, '\n')).replace(/\n/g, '<br>');
  }

  function inisial(s) {
    var kode = (s.kode || '').trim();
    if (kode) return kode.slice(0, 4);
    var w = String(s.orang || s.nama || '?').replace(/[^A-Za-z\u00C0-\u024F ]/g, ' ').trim().split(/\s+/);
    return (w[0] ? w[0][0] : '?') + (w[1] ? w[1][0] : '');
  }

  function fotoSrc(f) {
    // hanya nama file; folder tetap assets/personel/
    var name = String(f || '').split(/[\\/]/).pop();
    return name ? 'assets/personel/' + encodeURIComponent(name) : '';
  }

  function kartu(s) {
    var src = fotoSrc(s.foto);
    var ini = K.esc(inisial(s));
    var avatar = src
      ? '<div class="jabatan-avatar has-foto"><img src="' + src + '" alt="' + K.esc(s.orang || s.nama) + '" loading="lazy" onerror="this.parentNode.classList.remove(\'has-foto\');this.parentNode.textContent=this.getAttribute(\'data-ini\')" data-ini="' + ini + '"></div>'
      : '<div class="jabatan-avatar">' + ini + '</div>';
    return '<div class="jabatan-card">' + avatar +
      (s.orang ? '<div class="jabatan-orang">' + K.esc(s.orang) + '</div>' : '') +
      '<div class="jabatan-nama">' + K.esc(s.nama) + '</div>' +
      (s.posisi ? '<div class="jabatan-pos">' + multiline(s.posisi) + '</div>' : '') +
      '</div>';
  }

  function renderStruktur(wrap, list) {
    var byTier = {};
    list.forEach(function (s) {
      var t = TINGKAT.some(function (x) { return x.key === s.tingkat; }) ? s.tingkat : 'kasi';
      (byTier[t] = byTier[t] || []).push(s);
    });
    var html = '';
    TINGKAT.forEach(function (t) {
      var items = byTier[t.key];
      if (!items || !items.length) return;
      html += '<section class="struktur-tier"><div class="tier-label">' + K.esc(t.label) + '</div>';
      if (t.key === 'personel') {
        var groups = [], idx = {};
        items.forEach(function (s) {
          var u = (s.unit || '').trim();
          if (!(u in idx)) { idx[u] = groups.length; groups.push({ unit: u, items: [] }); }
          groups[idx[u]].items.push(s);
        });
        groups.forEach(function (g) {
          if (g.unit) html += '<div class="unit-label">Lab ' + K.esc(g.unit) + '</div>';
          html += '<div class="tier-row">' + g.items.map(kartu).join('') + '</div>';
        });
      } else {
        html += '<div class="tier-row">' + items.map(kartu).join('') + '</div>';
      }
      html += '</section>';
    });
    wrap.innerHTML = html;
  }

  function render(data) {
    var profilWrap = document.getElementById('profilWrap');
    if (profilWrap && data.profil) {
      profilWrap.innerHTML = data.profil.map(function (p) { return '<p>' + K.esc(p) + '</p>'; }).join('');
    }
    var idWrap = document.getElementById('identitasWrap');
    if (idWrap && data.identitas) {
      idWrap.innerHTML = data.identitas.map(function (row, i) {
        var borderStyle = (i < data.identitas.length - 1) ? 'border-bottom:1px solid rgba(255,255,255,0.1)' : '';
        return '<tr style="' + borderStyle + '"><td style="padding:9px 0;color:rgba(255,255,255,0.55);width:45%">' + K.esc(row.label) +
          '</td><td style="padding:9px 0;color:#fff;font-weight:500">' + K.esc(row.nilai) + '</td></tr>';
      }).join('');
    }
    var visiWrap = document.getElementById('visiWrap');
    if (visiWrap && data.visi) visiWrap.textContent = data.visi;
    var misiWrap = document.getElementById('misiWrap');
    if (misiWrap && data.misi) misiWrap.innerHTML = data.misi.map(function (m) { return '<li>' + K.esc(m) + '</li>'; }).join('');
    var strukturWrap = document.getElementById('strukturWrap');
    if (strukturWrap && data.struktur) renderStruktur(strukturWrap, data.struktur);
    var dhWrap = document.getElementById('dasarHukumWrap');
    if (dhWrap && data.dasarHukum) dhWrap.innerHTML = data.dasarHukum.map(function (d) { return '<li>' + K.esc(d) + '</li>'; }).join('');
  }

  fetch('konten-api.php?action=get&page=tentang')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () {});
})();
