(function () {
  var K = window.KontenShared;

  function statHTML(s) {
    return '<div class="kegiatan-card"><div class="kegiatan-num">' + K.esc(s.angka) + '</div>' +
      '<h3>' + K.esc(s.judul) + '</h3><p>' + K.esc(s.desc) + '</p></div>';
  }

  function tagClass(tag) {
    var t = tag.toLowerCase();
    if (t.indexOf('bimtek') !== -1) return 'tag-bimtek';
    if (t.indexOf('sertif') !== -1 || t.indexOf('akred') !== -1) return 'tag-sertifikasi';
    if (t.indexOf('kerja') !== -1) return 'tag-kerjasama';
    return 'tag-kegiatan';
  }

  function timelineHTML(item) {
    var tags = (item.tags || []).map(function (tag) {
      return '<span class="tag ' + tagClass(tag) + '">' + K.esc(tag) + '</span>';
    }).join('');
    return '<div class="timeline-item"><div class="timeline-dot"></div>' +
      '<div class="timeline-date">' + K.esc(item.tanggal) + '</div>' +
      '<div class="timeline-card"><h4>' + K.esc(item.judul) + '</h4><p>' + K.esc(item.desc) + '</p>' +
      '<div class="timeline-tags">' + tags + '</div></div></div>';
  }

  function render(data) {
    var statWrap = document.getElementById('kegiatanStatWrap');
    if (statWrap && data.statistik) statWrap.innerHTML = data.statistik.map(statHTML).join('');
    var tlWrap = document.getElementById('kegiatanTimelineWrap');
    if (tlWrap && data.timeline) tlWrap.innerHTML = data.timeline.map(timelineHTML).join('');
  }

  fetch('konten-api.php?action=get&page=kegiatan')
    .then(function (r) { return r.json(); })
    .then(function (res) { if (res && res.ok && res.data) render(res.data); })
    .catch(function () {});
})();
