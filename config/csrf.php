<?php
// ── CSRF HELPER ──────────────────────────────────────────────
// Include file ini di semua halaman admin yang punya form.
// Pastikan session_start() sudah dipanggil sebelum include ini.

/**
 * Generate token baru dan simpan di session.
 * Dipanggil sekali saat halaman form di-load.
 */
function csrf_generate(): string {
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Cetak hidden input dengan token — taruh di dalam setiap <form>.
 * Contoh pemakaian: <?php csrf_field(); ?>
 */
function csrf_field(): void {
    $token = csrf_generate();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verifikasi token dari form submission.
 * Kalau tidak valid, hentikan eksekusi dan redirect.
 */
function csrf_verify(): void {
    $token_form    = $_POST['csrf_token'] ?? '';
    $token_session = $_SESSION['csrf_token'] ?? '';

    // hash_equals() aman dari timing attack — jangan pakai === biasa
    if(empty($token_form) || empty($token_session) || !hash_equals($token_session, $token_form)){
        http_response_code(403);
        die('<p style="font-family:monospace;padding:40px 20px;color:#ff0044;background:#050508;">
             403 — Invalid CSRF token. Request ditolak.<br><br>
             <a href="javascript:history.back()" style="color:#ff0044;">← Kembali</a>
             </p>');
    }

    // Regenerate token setelah dipakai — tidak bisa dipakai dua kali
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}