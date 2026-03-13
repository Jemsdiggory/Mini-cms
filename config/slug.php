<?php
// ── SLUG HELPER ───────────────────────────────────────────────

/**
 * Convert judul artikel jadi slug URL-friendly.
 * "Review Elden Ring 2024!" → "review-elden-ring-2024"
 */
function generate_slug(string $title): string {
    $slug = strtolower(trim($title));

    // Ganti karakter khusus Indonesia yang umum
    $slug = str_replace(
        ['á','à','â','ä','ã','å','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','ö','õ','ú','ù','û','ü','ý','ñ','ç'],
        ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','y','n','c'],
        $slug
    );

    // Hapus semua karakter selain huruf, angka, spasi, dan strip
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

    // Ganti spasi dan strip berulang jadi satu strip
    $slug = preg_replace('/[\s-]+/', '-', $slug);

    // Trim strip di awal/akhir
    return trim($slug, '-');
}

/**
 * Pastikan slug unik di database.
 *
 * $exclude_id dipakai saat edit — skip artikel yang sedang diedit
 * supaya tidak bentrok dengan dirinya sendiri.
 */
function unique_slug(mysqli $conn, string $slug, int $exclude_id = 0): string {
    $original = $slug;
    $counter  = 1;

    while(true){
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM posts WHERE slug = ? AND id != ?"
        );
        mysqli_stmt_bind_param($stmt, "si", $slug, $exclude_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if(mysqli_stmt_num_rows($stmt) === 0){
            // Slug belum dipakai — aman
            return $slug;
        }

        // Sudah dipakai — tambah angka di belakang
        $slug = $original . '-' . $counter;
        $counter++;
    }
}