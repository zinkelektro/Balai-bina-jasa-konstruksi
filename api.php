<?php
/**
 * api.php — Backend UPTD Bina Jasa Konstruksi & Pengujian Material Konstruksi NTB
 * Penyimpanan: file JSON di storage/ (tanpa database, ringan untuk server ARM/HG680P)
 * Semua request: ?action=... (GET untuk baca publik, POST untuk tulis/admin)
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

define('STORAGE_DIR', __DIR__ . '/storage');
define('UPLOAD_DIR', STORAGE_DIR . '/uploads');
define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024); // 8MB

// ── Helper: baca / tulis JSON dengan file lock ──
function read_json($file, $default) {
    $path = STORAGE_DIR . '/' . $file;
    if (!file_exists($path)) return $default;
    $fp = fopen($path, 'r');
    if (!$fp) return $default;
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($content, true);
    return $data === null ? $default : $data;
}
function write_json($file, $data) {
    $path = STORAGE_DIR . '/' . $file;
    $fp = fopen($path, 'c');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail($msg, $code = 400) { respond(['ok' => false, 'error' => $msg], $code); }
function ok($extra = []) { respond(array_merge(['ok' => true], $extra)); }
function next_id($items) {
    $max = 0;
    foreach ($items as $it) { if (isset($it['id']) && $it['id'] > $max) $max = $it['id']; }
    return $max + 1;
}
function current_user() {
    return $_SESSION['user'] ?? null;
}
function is_logged_in() { return !empty($_SESSION['user']); }
function is_admin() { return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin'); }
function has_permission($permission) {
    $u = current_user();
    if (!$u) return false;
    if (($u['role'] ?? '') === 'admin') return true;
    $map = [
        'content' => ['editor'],
        'own_password' => ['editor'],
    ];
    return in_array($u['role'], $map[$permission] ?? [], true);
}
function require_login() { if (!is_logged_in()) fail('Tidak diizinkan. Silakan login.', 401); }
function require_admin() { if (!is_admin()) fail('Akses khusus admin.', 403); }
function require_permission($permission) {
    if (!is_logged_in()) fail('Tidak diizinkan. Silakan login.', 401);
    if (!has_permission($permission)) fail('Akun Anda tidak memiliki izin untuk tindakan ini.', 403);
}
function input_val($key, $default = null) {
    if (isset($_POST[$key])) return $_POST[$key];
    return $default;
}
function clean_str($s) { return trim(strip_tags((string)$s)); }

function handle_upload($field, $subdir, $allowedExt) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) fail('Gagal mengunggah berkas (kode error: ' . $f['error'] . ')');
    if ($f['size'] > MAX_UPLOAD_SIZE) fail('Ukuran berkas melebihi batas 8MB');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) fail('Tipe berkas .' . $ext . ' tidak diizinkan');
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $safeName = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $safeName)) fail('Gagal menyimpan berkas di server');
    return [
        'filename' => $safeName,
        'original_name' => $f['name'],
        'size' => $f['size'],
        'ext' => $ext,
    ];
}
function delete_upload($subdir, $filename) {
    if (!$filename) return;
    $path = UPLOAD_DIR . '/' . $subdir . '/' . $filename;
    if (file_exists($path)) @unlink($path);
}
function format_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function mime_for_ext($ext) {
    $map = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip',
    ];
    return $map[strtolower($ext)] ?? 'application/octet-stream';
}

// ── Konfigurasi default (dibuat otomatis saat pertama kali dipanggil) ──
$config = read_json('config.json', null);
if ($config === null) {
    $config = [
        'admin_password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ];
    write_json('config.json', $config);
}

/* ── Multi-akun ──
 * Admin = akses penuh + kelola akun.
 * Editor = berita, pengumuman, dan dokumen/berkas.
 * Migrasi otomatis: jika users.json belum ada, akun admin dibuat
 * dari hash admin lama sehingga instalasi lama tetap bisa dipakai.
 */
$users = read_json('users.json', null);
if ($users === null || !is_array($users)) {
    $users = [[
        'id' => 1,
        'username' => 'admin',
        'name' => 'Administrator',
        'role' => 'admin',
        'password_hash' => $config['admin_password_hash'] ?? password_hash('admin123', PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
    ]];
    write_json('users.json', $users);
}
function find_user_by_username($username) {
    $username = strtolower(trim((string)$username));
    $items = read_json('users.json', []);
    foreach ($items as $u) {
        if (strtolower($u['username'] ?? '') === $username) return $u;
    }
    return null;
}
function find_user_by_id($id) {
    $items = read_json('users.json', []);
    foreach ($items as $u) {
        if ((int)($u['id'] ?? 0) === (int)$id) return $u;
    }
    return null;
}
function sanitize_user($u) {
    return [
        'id' => (int)($u['id'] ?? 0),
        'username' => $u['username'] ?? '',
        'name' => $u['name'] ?? '',
        'role' => $u['role'] ?? 'editor',
        'created_at' => $u['created_at'] ?? '',
    ];
}
function valid_username($username) {
    return (bool)preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username);
}

// ── Helper: kontak WhatsApp instansi ──
function normalize_wa($raw) {
    $n = preg_replace('/\D+/', '', (string)$raw);
    if ($n === '') return '';
    if (strpos($n, '00') === 0) $n = substr($n, 2);
    if (strpos($n, '0') === 0) $n = '62' . substr($n, 1);
    elseif (strpos($n, '8') === 0) $n = '62' . $n;
    return $n;
}
function wa_config() {
    $c = read_json('kontak.json', []);
    $nomor = normalize_wa($c['wa_number'] ?? '');
    return [
        'wa_aktif' => !empty($c['wa_aktif']) && $nomor !== '',
        'wa_number' => $nomor,
        'wa_nama' => $c['wa_nama'] ?? 'Petugas Layanan Pengujian',
    ];
}
function wa_tampil($nomor) {
    // 6281234567890 -> +62 812-3456-7890
    if (strpos($nomor, '62') !== 0) return '+' . $nomor;
    $rest = substr($nomor, 2);
    return '+62 ' . trim(chunk_split($rest, 4, '-'), '-');
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ════════════════════ AUTH / MULTI-AKUN ════════════════════
    case 'login': {
        $username = strtolower(trim((string)input_val('username', '')));
        $password = (string)input_val('password', '');
        $u = find_user_by_username($username);
        if (!$u || $password === '' || !password_verify($password, $u['password_hash'] ?? '')) {
            fail('Username atau password salah', 401);
        }
        session_regenerate_id(true);
        $_SESSION['user'] = sanitize_user($u);
        ok(['user' => $_SESSION['user']]);
        break;
    }

    case 'logout': {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        ok();
        break;
    }

    case 'session_check': {
        respond(['ok' => true, 'loggedin' => is_logged_in(), 'user' => current_user()]);
        break;
    }

    case 'change_password': {
        require_login();
        $old = (string)input_val('old_password', '');
        $new = (string)input_val('new_password', '');
        if (strlen($new) < 8) fail('Password baru minimal 8 karakter');
        $uid = (int)(current_user()['id'] ?? 0);
        $items = read_json('users.json', []);
        $found = false;
        foreach ($items as &$u) {
            if ((int)$u['id'] === $uid) {
                if (!password_verify($old, $u['password_hash'] ?? '')) fail('Password lama salah');
                $u['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                $found = true;
                break;
            }
        }
        unset($u);
        if (!$found) fail('Akun tidak ditemukan', 404);
        write_json('users.json', $items);
        ok();
        break;
    }

    case 'list_users': {
        require_admin();
        $items = read_json('users.json', []);
        respond(['ok' => true, 'data' => array_map('sanitize_user', $items)]);
        break;
    }

    case 'create_user':
    case 'update_user': {
        require_admin();
        $items = read_json('users.json', []);
        $id = (int)input_val('id', 0);
        $username = strtolower(trim((string)input_val('username', '')));
        $name = clean_str(input_val('name', ''));
        $role = clean_str(input_val('role', 'editor'));
        $password = (string)input_val('password', '');

        if (!valid_username($username)) fail('Username 3-32 karakter: huruf, angka, titik, garis bawah, atau tanda minus.');
        if ($name === '') fail('Nama wajib diisi.');
        if (!in_array($role, ['admin', 'editor'], true)) fail('Role tidak valid.');

        foreach ($items as $u) {
            if (strtolower($u['username'] ?? '') === $username && (int)$u['id'] !== $id) {
                fail('Username sudah digunakan.');
            }
        }

        if ($id > 0) {
            $found = false;
            foreach ($items as &$u) {
                if ((int)$u['id'] === $id) {
                    if ($u['role'] === 'admin' && $role !== 'admin') {
                        $adminCount = count(array_filter($items, fn($x) => ($x['role'] ?? '') === 'admin'));
                        if ($adminCount <= 1) fail('Minimal harus ada satu akun admin.');
                    }
                    $u['username'] = $username;
                    $u['name'] = $name;
                    $u['role'] = $role;
                    if ($password !== '') {
                        if (strlen($password) < 8) fail('Password minimal 8 karakter.');
                        $u['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $found = true;
                    if ((int)(current_user()['id'] ?? 0) === $id) {
                        $_SESSION['user'] = sanitize_user($u);
                    }
                    break;
                }
            }
            unset($u);
            if (!$found) fail('Akun tidak ditemukan.', 404);
        } else {
            if (strlen($password) < 8) fail('Password minimal 8 karakter.');
            $items[] = [
                'id' => next_id($items),
                'username' => $username,
                'name' => $name,
                'role' => $role,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        write_json('users.json', $items);
        ok();
        break;
    }

    case 'delete_user': {
        require_admin();
        $id = (int)input_val('id', 0);
        if ($id === (int)(current_user()['id'] ?? 0)) fail('Akun yang sedang digunakan tidak dapat dihapus.');
        $items = read_json('users.json', []);
        $target = find_user_by_id($id);
        if (!$target) fail('Akun tidak ditemukan.', 404);
        if (($target['role'] ?? '') === 'admin') {
            $adminCount = count(array_filter($items, fn($x) => ($x['role'] ?? '') === 'admin'));
            if ($adminCount <= 1) fail('Minimal harus ada satu akun admin.');
        }
        $items = array_values(array_filter($items, fn($u) => (int)$u['id'] !== $id));
        write_json('users.json', $items);
        ok();
        break;
    }

    // ════════════════════ STATISTIK BERANDA ════════════════════
    case 'stats': {
        $stats = read_json('stats.json', ['tenaga' => 1248, 'uji' => 3870, 'pelatihan' => 34,
            'kabkota' => 10, 'lab' => 3, 'sla' => 4]);
        respond(['ok' => true, 'data' => $stats]);
        break;
    }

    case 'update_stats': {
        require_admin();
        $stats = [
            'tenaga' => (int) input_val('tenaga', 0),
            'uji' => (int) input_val('uji', 0),
            'pelatihan' => (int) input_val('pelatihan', 0),
            'kabkota' => (int) input_val('kabkota', 0),
            'lab' => (int) input_val('lab', 0),
            'sla' => (int) input_val('sla', 0),
        ];
        write_json('stats.json', $stats);
        ok();
        break;
    }

    // ════════════════════ KONTAK WHATSAPP INSTANSI ════════════════════
    case 'kontak_info': {
        $c = wa_config();
        respond(['ok' => true, 'data' => [
            'wa_aktif' => $c['wa_aktif'],
            'wa_number' => $c['wa_number'],
            'wa_tampil' => $c['wa_number'] !== '' ? wa_tampil($c['wa_number']) : '',
            'wa_nama' => $c['wa_nama'],
        ]]);
        break;
    }

    case 'update_kontak': {
        require_admin();
        $raw = clean_str(input_val('wa_number', ''));
        $nomor = normalize_wa($raw);
        $aktif = input_val('wa_aktif', '0') === '1';
        if ($nomor !== '' && (strlen($nomor) < 10 || strlen($nomor) > 15)) {
            fail('Nomor WhatsApp tidak valid. Contoh: 081234567890');
        }
        if ($aktif && $nomor === '') fail('Isi nomor WhatsApp terlebih dahulu untuk mengaktifkan fitur ini');
        $nama = clean_str(input_val('wa_nama', ''));
        write_json('kontak.json', [
            'wa_aktif' => $aktif,
            'wa_number' => $nomor,
            'wa_nama' => $nama !== '' ? $nama : 'Petugas Layanan Pengujian',
        ]);
        ok();
        break;
    }

    // ════════════════════ BERITA ════════════════════
    case 'list_berita': {
        $items = read_json('berita.json', []);
        usort($items, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
        $limit = (int) ($_GET['limit'] ?? 0);
        if ($limit > 0) $items = array_slice($items, 0, $limit);
        respond(['ok' => true, 'data' => $items]);
        break;
    }

    case 'get_berita': {
        $id = (int) ($_GET['id'] ?? 0);
        $items = read_json('berita.json', []);
        foreach ($items as $it) { if ($it['id'] === $id) respond(['ok' => true, 'data' => $it]); }
        fail('Berita tidak ditemukan', 404);
        break;
    }

    case 'create_berita':
    case 'update_berita': {
        require_permission('content');
        $items = read_json('berita.json', []);
        $id = (int) input_val('id', 0);
        $upload = handle_upload('gambar', 'berita', ['jpg', 'jpeg', 'png', 'webp']);
        $payload = [
            'tag' => clean_str(input_val('tag', 'Kegiatan')),
            'title' => clean_str(input_val('title', '')),
            'excerpt' => clean_str(input_val('excerpt', '')),
            'content' => clean_str(input_val('content', '')),
            'date' => clean_str(input_val('date', date('Y-m-d'))),
            'featured' => input_val('featured', '0') === '1',
        ];
        if ($payload['title'] === '') fail('Judul berita wajib diisi');
        if ($id > 0) {
            $found = false;
            foreach ($items as &$it) {
                if ($it['id'] === $id) {
                    if ($upload) { delete_upload('berita', $it['image'] ?? null); $payload['image'] = $upload['filename']; }
                    else $payload['image'] = $it['image'] ?? null;
                    $it = array_merge($it, $payload);
                    $found = true;
                    break;
                }
            }
            unset($it);
            if (!$found) fail('Berita tidak ditemukan', 404);
        } else {
            $payload['id'] = next_id($items);
            $payload['image'] = $upload ? $upload['filename'] : null;
            $items[] = $payload;
        }
        write_json('berita.json', $items);
        ok();
        break;
    }

    case 'delete_berita': {
        require_permission('content');
        $id = (int) input_val('id', 0);
        $items = read_json('berita.json', []);
        $items = array_values(array_filter($items, function ($it) use ($id) {
            if ($it['id'] === $id) { delete_upload('berita', $it['image'] ?? null); return false; }
            return true;
        }));
        write_json('berita.json', $items);
        ok();
        break;
    }

    // ════════════════════ PENGUMUMAN ════════════════════
    case 'list_pengumuman': {
        $items = read_json('pengumuman.json', []);
        usort($items, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
        $limit = (int) ($_GET['limit'] ?? 0);
        if ($limit > 0) $items = array_slice($items, 0, $limit);
        respond(['ok' => true, 'data' => $items]);
        break;
    }

    case 'create_pengumuman':
    case 'update_pengumuman': {
        require_permission('content');
        $items = read_json('pengumuman.json', []);
        $id = (int) input_val('id', 0);
        $payload = [
            'tag' => clean_str(input_val('tag', 'info')), // penting | jadwal | info
            'title' => clean_str(input_val('title', '')),
            'date' => clean_str(input_val('date', date('Y-m-d'))),
        ];
        if ($payload['title'] === '') fail('Judul pengumuman wajib diisi');

        // Lampiran pengumuman: PDF, Word, Excel, ZIP, atau gambar.
        $upload = handle_upload('lampiran', 'pengumuman', [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip',
            'jpg', 'jpeg', 'png', 'gif', 'webp'
        ]);

        if ($id > 0) {
            $found = false;
            foreach ($items as &$it) {
                if ((int)$it['id'] === $id) {
                    $oldLampiran = $it['lampiran'] ?? null;
                    $it = array_merge($it, $payload);
                    if ($upload) {
                        if ($oldLampiran && !empty($oldLampiran['filename'])) {
                            delete_upload('pengumuman', $oldLampiran['filename']);
                        }
                        $it['lampiran'] = $upload;
                    }
                    $found = true;
                    break;
                }
            }
            unset($it);
            if (!$found) fail('Pengumuman tidak ditemukan', 404);
        } else {
            $payload['id'] = next_id($items);
            if ($upload) $payload['lampiran'] = $upload;
            $items[] = $payload;
        }
        write_json('pengumuman.json', $items);
        ok();
        break;
    }

    case 'delete_pengumuman': {
        require_permission('content');
        $id = (int) input_val('id', 0);
        $items = read_json('pengumuman.json', []);
        $items = array_values(array_filter($items, function ($it) use ($id) {
            if ((int)$it['id'] === $id) {
                $lampiran = $it['lampiran'] ?? null;
                if (is_array($lampiran) && !empty($lampiran['filename'])) {
                    delete_upload('pengumuman', $lampiran['filename']);
                }
                return false;
            }
            return true;
        }));
        write_json('pengumuman.json', $items);
        ok();
        break;
    }

    // ════════════════════ UNDUHAN (dokumen/formulir) ════════════════════
    case 'list_unduhan': {
        $items = read_json('unduhan.json', []);
        respond(['ok' => true, 'data' => $items]);
        break;
    }

    case 'create_unduhan': {
        require_permission('content');
        $items = read_json('unduhan.json', []);
        $upload = handle_upload('berkas', 'unduhan', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']);
        if (!$upload) fail('Berkas wajib diunggah');
        $items[] = [
            'id' => next_id($items),
            'title' => clean_str(input_val('title', $upload['original_name'])),
            'kategori' => clean_str(input_val('kategori', 'Formulir')),
            'filename' => $upload['filename'],
            'ext' => $upload['ext'],
            'size' => format_size($upload['size']),
        ];
        write_json('unduhan.json', $items);
        ok();
        break;
    }

    case 'update_unduhan': {
        require_permission('content');
        $items = read_json('unduhan.json', []);
        $id = (int) input_val('id', 0);
        $upload = handle_upload('berkas', 'unduhan', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']);
        $found = false;
        foreach ($items as &$it) {
            if ($it['id'] === $id) {
                $it['title'] = clean_str(input_val('title', $it['title']));
                $it['kategori'] = clean_str(input_val('kategori', $it['kategori']));
                if ($upload) {
                    delete_upload('unduhan', $it['filename']);
                    $it['filename'] = $upload['filename'];
                    $it['ext'] = $upload['ext'];
                    $it['size'] = format_size($upload['size']);
                }
                $found = true;
                break;
            }
        }
        unset($it);
        if (!$found) fail('Dokumen tidak ditemukan', 404);
        write_json('unduhan.json', $items);
        ok();
        break;
    }

    case 'delete_unduhan': {
        require_permission('content');
        $id = (int) input_val('id', 0);
        $items = read_json('unduhan.json', []);
        $items = array_values(array_filter($items, function ($it) use ($id) {
            if ($it['id'] === $id) { delete_upload('unduhan', $it['filename']); return false; }
            return true;
        }));
        write_json('unduhan.json', $items);
        ok();
        break;
    }

    // ════════════════════ UNDUH BERKAS (publik / admin) ════════════════════
    case 'download': {
        $type = $_GET['type'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        $inline = !empty($_GET['inline']);

        if ($type === 'unduhan') {
            $items = read_json('unduhan.json', []);
            foreach ($items as $it) {
                if ((int)$it['id'] === $id) {
                    $path = UPLOAD_DIR . '/unduhan/' . $it['filename'];
                    if (!file_exists($path)) fail('Berkas tidak ditemukan di server', 404);
                    $ext = strtolower($it['ext'] ?? pathinfo($path, PATHINFO_EXTENSION));
                    $mime = mime_for_ext($ext);
                    header('Content-Type: ' . $mime);
                    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename(($it['title'] ?: 'dokumen') . '.' . $ext) . '"');
                    header('Content-Length: ' . filesize($path));
                    header('Cache-Control: public, max-age=3600');
                    readfile($path);
                    exit;
                }
            }
            fail('Dokumen tidak ditemukan', 404);

        } elseif ($type === 'pengumuman') {
            $items = read_json('pengumuman.json', []);
            foreach ($items as $it) {
                $lampiran = $it['lampiran'] ?? null;
                if ((int)$it['id'] === $id && is_array($lampiran) && !empty($lampiran['filename'])) {
                    $path = UPLOAD_DIR . '/pengumuman/' . $lampiran['filename'];
                    if (!file_exists($path)) fail('Lampiran tidak ditemukan di server', 404);
                    $ext = strtolower($lampiran['ext'] ?? pathinfo($path, PATHINFO_EXTENSION));
                    $mime = mime_for_ext($ext);
                    header('Content-Type: ' . $mime);
                    $name = $lampiran['original_name'] ?? ('lampiran.' . $ext);
                    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($name) . '"');
                    header('Content-Length: ' . filesize($path));
                    header('Cache-Control: public, max-age=3600');
                    readfile($path);
                    exit;
                }
            }
            fail('Lampiran pengumuman tidak ditemukan', 404);

        } elseif ($type === 'lampiran') {
            require_admin();
            $items = read_json('permohonan.json', []);
            foreach ($items as $it) {
                if ($it['id'] === $id && !empty($it['lampiran'])) {
                    $path = UPLOAD_DIR . '/lampiran/' . $it['lampiran'];
                    if (!file_exists($path)) fail('Berkas tidak ditemukan di server', 404);
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    header('Content-Type: ' . mime_for_ext($ext));
                    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($it['lampiran']) . '"');
                    header('Content-Length: ' . filesize($path));
                    readfile($path);
                    exit;
                }
            }
            fail('Lampiran tidak ditemukan', 404);
        } else {
            fail('Tipe unduhan tidak dikenal');
        }
    }

    // ════════════════════ PERMOHONAN LAYANAN ════════════════════
    case 'submit_permohonan': {
        $items = read_json('permohonan.json', []);
        $nama = clean_str(input_val('nama', ''));
        $noHp = clean_str(input_val('no_hp', ''));
        $jenis = clean_str(input_val('jenis_layanan', ''));
        if ($nama === '' || $noHp === '' || $jenis === '') fail('Nama, No. HP, dan Jenis Layanan wajib diisi');
        $upload = handle_upload('lampiran', 'lampiran', ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'zip']);
        $kode = 'TIK-' . date('ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $item = [
            'id' => next_id($items),
            'kode_tiket' => $kode,
            'nama' => $nama,
            'instansi' => clean_str(input_val('instansi', '')),
            'no_hp' => $noHp,
            'email' => clean_str(input_val('email', '')),
            'jenis_layanan' => $jenis,
            'catatan' => clean_str(input_val('catatan', '')),
            'lampiran' => $upload ? $upload['filename'] : null,
            'status' => 'diajukan', // diajukan | diproses | selesai | ditolak
            'catatan_admin' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $items[] = $item;
        write_json('permohonan.json', $items);

        // Panel kontak WhatsApp instansi (dibuka otomatis oleh browser setelah permohonan terkirim)
        $out = ['kode_tiket' => $kode];
        $wa = wa_config();
        if ($wa['wa_aktif']) {
            $pesan = "Halo " . $wa['wa_nama'] . " UPTD BJKPMK NTB,\n"
                . "saya telah mengajukan permohonan melalui website.\n\n"
                . "Kode Tiket: " . $kode . "\n"
                . "Jenis Layanan: " . $jenis . "\n"
                . "Nama: " . $nama . "\n"
                . ($item['instansi'] !== '' ? "Instansi: " . $item['instansi'] . "\n" : '')
                . "No. HP: " . $noHp . "\n"
                . ($item['catatan'] !== '' ? "Catatan: " . $item['catatan'] . "\n" : '')
                . "\nMohon tindak lanjutnya. Terima kasih.";
            $out['wa'] = [
                'url' => 'https://wa.me/' . $wa['wa_number'] . '?text=' . rawurlencode($pesan),
                'nama' => $wa['wa_nama'],
                'nomor' => wa_tampil($wa['wa_number']),
            ];
        }
        ok($out);
        break;
    }

    case 'cek_status': {
        $kode = strtoupper(trim($_GET['kode'] ?? ''));
        $kontak = trim($_GET['kontak'] ?? '');
        if ($kode === '' || $kontak === '') fail('Kode tiket dan No. HP/Email wajib diisi');
        $items = read_json('permohonan.json', []);
        foreach ($items as $it) {
            if (strtoupper($it['kode_tiket']) === $kode &&
                ($it['no_hp'] === $kontak || strcasecmp($it['email'], $kontak) === 0)) {
                respond(['ok' => true, 'data' => [
                    'kode_tiket' => $it['kode_tiket'],
                    'nama' => $it['nama'],
                    'jenis_layanan' => $it['jenis_layanan'],
                    'status' => $it['status'],
                    'catatan_admin' => $it['catatan_admin'],
                    'created_at' => $it['created_at'],
                    'updated_at' => $it['updated_at'],
                ]]);
            }
        }
        fail('Data tidak ditemukan. Periksa kembali kode tiket dan kontak Anda.', 404);
        break;
    }

    case 'list_permohonan': {
        require_admin();
        $items = read_json('permohonan.json', []);
        usort($items, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        respond(['ok' => true, 'data' => $items]);
        break;
    }

    case 'update_permohonan_status': {
        require_admin();
        $id = (int) input_val('id', 0);
        $status = clean_str(input_val('status', ''));
        $catatanAdmin = clean_str(input_val('catatan_admin', ''));
        if (!in_array($status, ['diajukan', 'diproses', 'selesai', 'ditolak'])) fail('Status tidak valid');
        $items = read_json('permohonan.json', []);
        $found = false;
        foreach ($items as &$it) {
            if ($it['id'] === $id) {
                $it['status'] = $status;
                $it['catatan_admin'] = $catatanAdmin;
                $it['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($it);
        if (!$found) fail('Permohonan tidak ditemukan', 404);
        write_json('permohonan.json', $items);
        ok();
        break;
    }

    case 'delete_permohonan': {
        require_admin();
        $id = (int) input_val('id', 0);
        $items = read_json('permohonan.json', []);
        $items = array_values(array_filter($items, function ($it) use ($id) {
            if ($it['id'] === $id) { delete_upload('lampiran', $it['lampiran'] ?? null); return false; }
            return true;
        }));
        write_json('permohonan.json', $items);
        ok();
        break;
    }

    default:
        fail('Aksi tidak dikenal: ' . htmlspecialchars($action), 404);
}
