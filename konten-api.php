<?php
/**
 * konten-api.php — editor konten halaman publik.
 * Memakai sesi login yang sama dengan api.php (admin/editor).
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$DATA_DIR = __DIR__ . '/konten-data';
$ALLOWED_PAGES = ['beranda', 'pengujian', 'kegiatan', 'peralatan', 'laboratorium', 'pelayanan', 'tentang'];

function out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function current_user() { return $_SESSION['user'] ?? null; }
function is_logged_in() { return !empty($_SESSION['user']['id']); }
function has_content_access() {
  return in_array($_SESSION['user']['role'] ?? '', ['admin','editor'], true);
}
function require_content_access() {
  if (!is_logged_in()) out(['ok'=>false,'error'=>'Sesi login tidak ditemukan. Silakan login melalui admin.html.'],401);
  if (!has_content_access()) out(['ok'=>false,'error'=>'Akun Anda tidak memiliki izin mengelola konten.'],403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'status') {
  out(['ok'=>true,'loggedIn'=>is_logged_in(),'user'=>current_user(),'canEdit'=>has_content_access()]);
}
if ($action === 'get') {
  $page = $_GET['page'] ?? '';
  if (!in_array($page,$ALLOWED_PAGES,true)) out(['ok'=>false,'error'=>'Halaman tidak dikenal'],400);
  $file = $DATA_DIR.'/'.$page.'.json';
  if (!file_exists($file)) out(['ok'=>false,'error'=>'Belum ada data tersimpan'],404);
  $raw = file_get_contents($file);
  $data = json_decode($raw,true);
  if ($data === null && trim($raw) !== 'null') out(['ok'=>false,'error'=>'Data JSON rusak'],500);
  out(['ok'=>true,'data'=>$data]);
}
if ($action === 'save') {
  require_content_access();
  $page = $_GET['page'] ?? $_POST['page'] ?? '';
  if (!in_array($page,$ALLOWED_PAGES,true)) out(['ok'=>false,'error'=>'Halaman tidak dikenal'],400);
  $data = json_decode(file_get_contents('php://input'),true);
  if (!is_array($data)) out(['ok'=>false,'error'=>'Data tidak valid (bukan JSON object/array)'],400);
  if (!is_dir($DATA_DIR) && !@mkdir($DATA_DIR,0775,true)) {
    out(['ok'=>false,'error'=>'Folder konten-data tidak dapat dibuat. Periksa permission server.'],500);
  }
  $file = $DATA_DIR.'/'.$page.'.json';
  if (file_exists($file)) @copy($file,$DATA_DIR.'/'.$page.'.backup.json');
  $json = json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $tmp = $file.'.tmp.'.bin2hex(random_bytes(4));
  if (@file_put_contents($tmp,$json,LOCK_EX) === false || !@rename($tmp,$file)) {
    @unlink($tmp);
    out(['ok'=>false,'error'=>'Gagal menyimpan. Pastikan folder konten-data dapat ditulis oleh PHP.'],500);
  }
  out(['ok'=>true,'saved_by'=>current_user()['username'] ?? '']);
}
// ── Foto personel (disimpan di assets/personel/, bukan di storage/) ──
$FOTO_DIR = __DIR__ . '/assets/personel';
$FOTO_EXT = ['jpg','jpeg','png','webp'];
$FOTO_MAX = 8 * 1024 * 1024; // 8 MB, sama dengan batas upload di api.php

if ($action === 'list_foto') {
  require_content_access();
  $files = [];
  if (is_dir($FOTO_DIR)) {
    foreach (scandir($FOTO_DIR) as $f) {
      if ($f[0] === '.') continue;
      if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $FOTO_EXT, true)) $files[] = $f;
    }
  }
  out(['ok'=>true,'files'=>$files]);
}
if ($action === 'upload_foto') {
  require_content_access();
  if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) out(['ok'=>false,'error'=>'Tidak ada file yang dipilih'],400);
  $f = $_FILES['foto'];
  if ($f['error'] !== UPLOAD_ERR_OK) out(['ok'=>false,'error'=>'Gagal mengunggah (kode error: '.$f['error'].'). Cek batas upload_max_filesize PHP.'],400);
  if ($f['size'] > $FOTO_MAX) out(['ok'=>false,'error'=>'Ukuran foto melebihi batas 8 MB'],400);
  $info = @getimagesize($f['tmp_name']);
  $map = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_WEBP=>'webp'];
  if (!$info || !isset($map[$info[2]])) out(['ok'=>false,'error'=>'File bukan gambar JPG, PNG, atau WEBP yang valid'],400);
  $ext = $map[$info[2]];
  if (!is_dir($FOTO_DIR) && !@mkdir($FOTO_DIR,0775,true)) out(['ok'=>false,'error'=>'Folder assets/personel tidak dapat dibuat. Periksa permission server.'],500);
  if (!is_writable($FOTO_DIR)) out(['ok'=>false,'error'=>'Folder assets/personel tidak dapat ditulis oleh PHP. Beri permission untuk www-data.'],500);
  $name = 'personel_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
  $dest = $FOTO_DIR.'/'.$name;

  // Perkecil otomatis (maks 600px) bila GD tersedia, agar halaman ringan di server ARM
  $done = false;
  $maxSide = 600;
  if (function_exists('imagecreatetruecolor') && ($info[0] > $maxSide || $info[1] > $maxSide)) {
    $src = null;
    if ($ext === 'jpg' && function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($f['tmp_name']);
    elseif ($ext === 'png' && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($f['tmp_name']);
    elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($f['tmp_name']);
    if ($src) {
      if ($ext === 'jpg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($f['tmp_name']);
        $o = $exif['Orientation'] ?? 1;
        $deg = [3=>180, 6=>-90, 8=>90][$o] ?? 0;
        if ($deg) { $rot = imagerotate($src, $deg, 0); if ($rot) { imagedestroy($src); $src = $rot; } }
      }
      $w = imagesx($src); $h = imagesy($src);
      $ratio = min($maxSide/$w, $maxSide/$h);
      $nw = max(1,(int)round($w*$ratio)); $nh = max(1,(int)round($h*$ratio));
      $dst = imagecreatetruecolor($nw,$nh);
      if ($ext !== 'jpg') { imagealphablending($dst,false); imagesavealpha($dst,true); }
      imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
      if ($ext === 'jpg') $done = imagejpeg($dst,$dest,85);
      elseif ($ext === 'png') $done = imagepng($dst,$dest,6);
      else $done = imagewebp($dst,$dest,85);
      imagedestroy($src); imagedestroy($dst);
    }
  }
  if (!$done && !move_uploaded_file($f['tmp_name'],$dest)) out(['ok'=>false,'error'=>'Gagal menyimpan foto di server'],500);
  out(['ok'=>true,'filename'=>$name]);
}
out(['ok'=>false,'error'=>'Aksi tidak dikenal'],400);
?>
