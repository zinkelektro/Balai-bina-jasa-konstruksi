/* konten-shared.js
 * Preset tema warna + ikon yang dipakai bersama oleh halaman publik
 * (pengujian-material, peralatan, laboratorium, pelayanan) dan panel admin.
 * Simpan hanya KEY tema/ikon di data JSON, bukan kode warna/SVG mentah,
 * supaya konten yang diisi admin tetap konsisten dengan desain situs.
 */
(function (global) {
  var TEMA = {
    navy: { bg: '#e8edf5', fg: '#0D2545', dot: '#0D2545' },
    gold: { bg: '#fdf3d8', fg: '#8B6200', dot: '#8B6200' },
    teal: { bg: '#e0f4ef', fg: '#14826E', dot: '#14826E' },
    red:  { bg: '#fce8e8', fg: '#C0272D', dot: '#C0272D' },
    gray: { bg: '#EEF0F4', fg: '#5A6478', dot: '#5A6478' }
  };

  var IKON = {
    beton: '<path d="M9 2h6M10 2v6.5L4.5 19a1 1 0 0 0 .9 1.5h13.2a1 1 0 0 0 .9-1.5L14 8.5V2"/><path d="M7 15h10"/>',
    aspal: '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    tanah: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
    baja:  '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
    dokumen: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
    alat: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.55V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.55 1H21a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1z"/>',
    lab: '<path d="M9 2v6.5L3.5 18a2 2 0 0 0 1.7 3h13.6a2 2 0 0 0 1.7-3L14.5 8.5V2"/><path d="M8 2h8"/><path d="M6.5 14h11"/>',
    sertifikat: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
    jam: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>',
    buku: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
    pensil: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'
  };

  var IKON_LABEL = {
    beton: 'Beton (kolom)', aspal: 'Aspal (drum)', tanah: 'Tanah (lapisan)',
    baja: 'Baja (struktur)', dokumen: 'Dokumen', alat: 'Alat (gear)', lab: 'Laboratorium (flask)',
    sertifikat: 'Sertifikat (dokumen bergaris)', jam: 'Jam / Waktu', buku: 'Buku / Pelatihan', pensil: 'Pensil / Konsultasi'
  };

  function esc(s) {
    if (s === undefined || s === null) return '';
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function svgIcon(key, size) {
    size = size || 24;
    var path = IKON[key] || IKON.dokumen;
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + path + '</svg>';
  }

  function temaOf(key) {
    return TEMA[key] || TEMA.navy;
  }

  global.KontenShared = {
    TEMA: TEMA, IKON: IKON, IKON_LABEL: IKON_LABEL,
    esc: esc, svgIcon: svgIcon, temaOf: temaOf
  };
})(window);
