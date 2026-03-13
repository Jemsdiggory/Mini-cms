<?php
session_start();
include("../config/database.php");

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if($id > 0){
    // ambil nama file gambar 
    $stmt = mysqli_prepare($conn, "SELECT image FROM posts WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if($row){
        // Hapus file gambar (kal0 ada()
        if(!empty($row['image']) && file_exists("../uploads/" . $row['image'])){
            unlink("../uploads/" . $row['image']);
        }

        // Hapus post dari database
        $del = mysqli_prepare($conn, "DELETE FROM posts WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $id);
        mysqli_stmt_execute($del);
    }
}

header("Location: dashboard.php");
exit();