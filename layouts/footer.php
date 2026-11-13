<?php
/**
 * Layout Footer Utama SIAKAD + Modal Konfirmasi Hapus
 */
?>
<?php if (current_user()): ?>
        </main>
    </div>
<?php endif; ?>

<!-- Modal Konfirmasi Hapus Global -->
<dialog id="modal-confirm-delete" class="modal no-print">
    <div class="modal-box max-w-md bg-white border border-slate-200 rounded-lg p-6 shadow-lg">
        <h3 class="font-bold text-base text-slate-800">Konfirmasi Penghapusan</h3>
        <p id="modal-delete-message" class="py-3 text-sm text-slate-600">Apakah Anda yakin ingin menghapus data ini?</p>
        <div class="modal-action mt-4 flex justify-end gap-2">
            <button type="button" id="btn-cancel-delete" class="btn btn-sm h-9 px-4 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border-none">Batal</button>
            <button type="button" id="btn-confirm-delete" class="btn btn-sm h-9 px-4 rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-none">Hapus</button>
        </div>
    </div>
</dialog>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
