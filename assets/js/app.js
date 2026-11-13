/**
 * Skrip Interaksi Antarmuka SIAKAD (Vanilla JS)
 * - Toggle sidebar mobile
 * - Auto-dismiss flash alert
 * - Modal konfirmasi hapus
 * - Live preview nilai akhir & huruf pada halaman input nilai dosen
 * - Pilih semua (check-all) pada halaman verifikasi KRS admin
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Toggle Sidebar pada Mobile
    const btnMenu = document.getElementById('btn-toggle-sidebar');
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    function closeSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }
    }

    function openSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        }
    }

    if (btnMenu) {
        btnMenu.addEventListener('click', function () {
            if (sidebar.classList.contains('-translate-x-full')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // 2. Auto-dismiss Flash Alert setelah 5 detik
    const flashAlert = document.getElementById('flash-alert');
    if (flashAlert) {
        setTimeout(function () {
            flashAlert.style.transition = 'opacity 0.35s ease';
            flashAlert.style.opacity = '0';
            setTimeout(function () {
                if (flashAlert.parentNode) {
                    flashAlert.parentNode.removeChild(flashAlert);
                }
            }, 350);
        }, 5000);
    }

    // 3. Modal Konfirmasi Hapus (untuk semua form ber-atribut data-confirm)
    const modalDelete = document.getElementById('modal-confirm-delete');
    const modalDeleteMessage = document.getElementById('modal-delete-message');
    const btnConfirmDelete = document.getElementById('btn-confirm-delete');
    const btnCancelDelete = document.getElementById('btn-cancel-delete');
    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === 'true') {
                return;
            }
            e.preventDefault();
            pendingForm = form;
            const msg = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (modalDeleteMessage) {
                modalDeleteMessage.textContent = msg;
            }
            if (modalDelete && typeof modalDelete.showModal === 'function') {
                modalDelete.showModal();
            } else if (window.confirm(msg)) {
                form.dataset.confirmed = 'true';
                form.submit();
            }
        });
    });

    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.dataset.confirmed = 'true';
                pendingForm.submit();
            }
        });
    }

    if (btnCancelDelete && modalDelete) {
        btnCancelDelete.addEventListener('click', function () {
            pendingForm = null;
            modalDelete.close();
        });
    }

    // 4. Live Preview Perhitungan Nilai Akhir & Huruf (Halaman Input Nilai Dosen)
    function konversiHuruf(na) {
        if (na >= 80) return { huruf: 'A', bobot: '4.00' };
        if (na >= 75) return { huruf: 'B+', bobot: '3.50' };
        if (na >= 70) return { huruf: 'B', bobot: '3.00' };
        if (na >= 65) return { huruf: 'C+', bobot: '2.50' };
        if (na >= 60) return { huruf: 'C', bobot: '2.00' };
        if (na >= 50) return { huruf: 'D', bobot: '1.00' };
        return { huruf: 'E', bobot: '0.00' };
    }

    document.querySelectorAll('tr[data-nilai-row]').forEach(function (row) {
        const inpTugas = row.querySelector('.inp-tugas');
        const inpUts   = row.querySelector('.inp-uts');
        const inpUas   = row.querySelector('.inp-uas');
        const outAkhir = row.querySelector('.out-nilai-akhir');
        const outHuruf = row.querySelector('.out-huruf');
        const outBobot = row.querySelector('.out-bobot');

        function updatePreview() {
            const t = parseFloat(inpTugas ? inpTugas.value : 0) || 0;
            const u = parseFloat(inpUts ? inpUts.value : 0) || 0;
            const a = parseFloat(inpUas ? inpUas.value : 0) || 0;
            const na = Math.round(((t * 0.30) + (u * 0.30) + (a * 0.40)) * 100) / 100;
            const konv = konversiHuruf(na);

            if (outAkhir) outAkhir.textContent = na.toFixed(2);
            if (outHuruf) outHuruf.textContent = konv.huruf;
            if (outBobot) outBobot.textContent = konv.bobot;
        }

        [inpTugas, inpUts, inpUas].forEach(function (inp) {
            if (inp) {
                inp.addEventListener('input', updatePreview);
            }
        });
    });

    // 5. Checkbox Pilih Semua (Halaman Verifikasi KRS Admin)
    const checkAllKrs = document.getElementById('check-all-krs');
    if (checkAllKrs) {
        checkAllKrs.addEventListener('change', function () {
            document.querySelectorAll('.check-item-krs').forEach(function (cb) {
                cb.checked = checkAllKrs.checked;
            });
        });
    }
});
