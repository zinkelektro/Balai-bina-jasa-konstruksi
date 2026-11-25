/* ============================================================
   AJUKAN LAYANAN - MODAL GLOBAL UNTUK HALAMAN INTERNAL
   Membuka form langsung di laboratorium.html/peralatan.html/etc.
   tanpa redirect ke index.html.
   ============================================================ */
(function () {
  'use strict';

  var API = 'api.php';
  var modalReady = false;
  var modalLoading = false;
  var modalWaiters = [];
  var WA = { aktif: false };

  function runWaiters() {
    var list = modalWaiters.slice();
    modalWaiters = [];
    list.forEach(function (fn) { try { fn(); } catch (e) {} });
  }

  function injectModalFromIndex() {
    if (modalReady || modalLoading) return;
    modalLoading = true;

    fetch('index.html', { cache: 'no-store' })
      .then(function (r) {
        if (!r.ok) throw new Error('Gagal memuat index.html');
        return r.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var srcModal = doc.getElementById('modalLayanan');
        if (!srcModal) throw new Error('Modal layanan tidak ditemukan');

        var modal = document.importNode(srcModal, true);
        document.body.appendChild(modal);

        // Handler klik overlay.
        modal.addEventListener('click', function (e) {
          if (e.target === modal) tutupModal('modalLayanan');
        });

        modalReady = true;
        modalLoading = false;
        runWaiters();
      })
      .catch(function (err) {
        modalLoading = false;
        console.error('Ajukan Layanan:', err);
        modalWaiters.forEach(function (fn) {
          try { fn(err); } catch (e) {}
        });
        modalWaiters = [];
      });
  }

  function bukaModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  window.tutupModal = function (id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
    document.body.style.overflow = '';
  };

  function setLayananStep(n) {
    var stepIsi = document.getElementById('stepIsi');
    var stepTiket = document.getElementById('stepTiket');
    var line = document.getElementById('stepLine');
    if (!stepIsi || !stepTiket || !line) return;

    stepIsi.className = 'm-step' + (n >= 1 ? ' active' : '');
    if (n >= 2) {
      stepIsi.className = 'm-step done';
      stepTiket.className = 'm-step active';
      line.className = 'm-line done';
    } else {
      stepTiket.className = 'm-step';
      line.className = 'm-line';
    }
  }

  function tampilPanelWa(wa, waWin) {
    var panel = document.getElementById('waPanel');
    if (!panel) return;

    if (!wa || !wa.url) {
      try { if (waWin) waWin.close(); } catch (e) {}
      panel.style.display = 'none';
      return;
    }

    document.getElementById('waPanelNama').textContent = wa.nama || '';
    document.getElementById('waPanelNomor').textContent = wa.nomor || '';

    var btn = document.getElementById('waPanelBtn');
    var status = document.getElementById('waPanelStatus');
    btn.href = wa.url;
    panel.style.display = '';

    var terbuka = false;
    try {
      if (waWin && !waWin.closed) {
        waWin.location.href = wa.url;
        terbuka = true;
      } else {
        terbuka = !!window.open(wa.url, '_blank');
      }
    } catch (e) {}

    status.textContent = terbuka
      ? 'WhatsApp dibuka otomatis di tab baru dengan pesan berisi kode tiket Anda. Tekan Kirim di WhatsApp untuk menghubungi petugas.'
      : 'Silakan tekan tombol Buka WhatsApp di bawah.';
  }

  window.kirimFormLayanan = function (e) {
    e.preventDefault();

    var form = e.target;
    var btn = document.getElementById('btnKirimLayanan');
    var msg = document.getElementById('layananMsg');
    if (!form || !btn || !msg) return false;

    msg.className = 'modal-msg';
    msg.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Mengirim...';

    var waWin = null;
    if (WA.aktif) {
      try {
        waWin = window.open('', '_blank');
        if (waWin) {
          waWin.document.write('<p style="font:16px system-ui;padding:24px">Menyiapkan WhatsApp&hellip;</p>');
        }
      } catch (err) { waWin = null; }
    }

    function tutupWaWin() {
      try { if (waWin) waWin.close(); } catch (err) {}
    }

    var fd = new FormData(form);
    fd.append('action', 'submit_permohonan');

    fetch(API, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        btn.textContent = 'Kirim Permohonan';

        if (!res.ok) {
          tutupWaWin();
          msg.className = 'modal-msg err show';
          msg.textContent = res.error || 'Gagal mengirim permohonan.';
          return;
        }

        form.style.display = 'none';

        var kode = document.getElementById('kodeTiketHasil');
        var hasil = document.getElementById('layananHasil');
        if (kode) kode.textContent = res.kode_tiket || '';
        if (hasil) hasil.style.display = '';

        setLayananStep(2);
        tampilPanelWa(res.wa, waWin);
      })
      .catch(function () {
        tutupWaWin();
        btn.disabled = false;
        btn.textContent = 'Kirim Permohonan';
        msg.className = 'modal-msg err show';
        msg.textContent = 'Tidak dapat terhubung ke server. Coba lagi nanti.';
      });

    return false;
  };

  function bukaFormLayananInternal(jenis) {
    var form = document.getElementById('formLayanan');
    var hasil = document.getElementById('layananHasil');
    var waPanel = document.getElementById('waPanel');
    var msg = document.getElementById('layananMsg');
    var select = document.getElementById('jenisLayananSelect');

    if (!form) return;

    form.style.display = '';
    if (hasil) hasil.style.display = 'none';
    if (waPanel) waPanel.style.display = 'none';
    if (msg) {
      msg.className = 'modal-msg';
      msg.textContent = '';
    }

    if (select && jenis) {
      var found = false;
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === jenis ||
            select.options[i].text === jenis) {
          select.selectedIndex = i;
          found = true;
          break;
        }
      }
      if (!found) select.value = '';
    }

    setLayananStep(1);
    bukaModal('modalLayanan');
  }

  window.bukaFormLayananPage = function (e, jenis) {
    if (e) e.preventDefault();

    function openNow() {
      bukaFormLayananInternal((function(){ try { return decodeURIComponent(jenis || ''); } catch(e) { return jenis || ''; } })());
    }

    if (modalReady) {
      openNow();
      return false;
    }

    modalWaiters.push(openNow);
    injectModalFromIndex();
    return false;
  };

  // Preload modal agar klik pertama terasa instan.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectModalFromIndex);
  } else {
    injectModalFromIndex();
  }

  // Ambil konfigurasi WhatsApp dari backend yang sama.
  fetch(API + '?action=kontak_info', { cache: 'no-store' })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res.ok || !res.data || !res.data.wa_aktif) return;
      WA = {
        aktif: true,
        nomor: res.data.wa_number,
        tampil: res.data.wa_tampil
      };
    })
    .catch(function () {});

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var modal = document.getElementById('modalLayanan');
      if (modal && modal.classList.contains('open')) {
        tutupModal('modalLayanan');
      }
    }
  });
})();
