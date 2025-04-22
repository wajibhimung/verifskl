<?php
// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Daftar penandatangan yang dipercaya
$trusted_signers = ['- Dra. Hj. Helwatin Najwa'];
$data = [];
$is_modified = true;
$integrity_status = '❌ Dokumen Telah Mengalami Perubahan atau Tidak Terverifikasi.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_file']['tmp_name'];
        $fileName = basename($_FILES['pdf_file']['name']);
        $uploadDir = __DIR__ . '/uploads/';
        $uploadPath = $uploadDir . $fileName;

        // Pastikan folder upload ada
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Upload file
        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            if (file_exists($uploadPath)) {
                $escapedPath = escapeshellarg($uploadPath);
                $output = shell_exec("pdfsig $escapedPath");

                if ($output) {
                    $lines = explode("\n", $output);
                    $signer_name = '';

                    foreach ($lines as $line) {
                        if (stripos($line, 'Signer Certificate Common Name:') !== false) {
                            $signer_name = trim(str_replace('Signer Certificate Common Name:', '', $line));
                            $data['🧾 Nama Penandatangan'] = $signer_name;
                        } elseif (stripos($line, 'Signing Time:') !== false) {
                            $data['⏰ Waktu Tanda Tangan'] = trim(str_replace('Signing Time:', '', $line));
                        } elseif (stripos($line, 'Signature Validation:') !== false) {
                            $data['✅ Validasi Tanda Tangan'] = trim(str_replace('Signature Validation:', '', $line));
                        } elseif (stripos($line, 'Certificate Validation:') !== false) {
                            $data['🔒 Status Sertifikat'] = trim(str_replace('Certificate Validation:', '', $line));
                        } elseif (stripos($line, 'Signer full Distinguished Name:') !== false) {
                            $data['📛 Identitas Lengkap'] = trim(str_replace('Signer full Distinguished Name:', '', $line));
                        } elseif (stripos($line, 'Location:') !== false) {
                            $data['📍 Lokasi'] = trim(str_replace('Location:', '', $line));
                        } elseif (stripos($line, 'Reason:') !== false) {
                            $data['📝 Alasan'] = trim(str_replace('Reason:', '', $line));
                        }
                    }

                    // Normalisasi nama untuk cocokkan dengan trusted signer
                    $normalized = trim(preg_replace('/\s+/', ' ', $signer_name));
                    $trusted_list = array_map(fn($n) => trim(preg_replace('/\s+/', ' ', $n)), $trusted_signers);

                    if (in_array($normalized, $trusted_list)) {
                        $data['🔒 Status Sertifikat'] = '✅ OK (Trusted by SMKN 1 Telagasari)';
                    }

                    if (isset($data['✅ Validasi Tanda Tangan']) && stripos($data['✅ Validasi Tanda Tangan'], 'valid') !== false) {
                        $is_modified = false;
                        $integrity_status = '✅ Dokumen LULUS VERIFIKASI dan BELUM mengalami perubahan.';
                    }
                } else {
                    $data['error'] = 'Gagal membaca hasil verifikasi dari pdfsig.';
                }
            } else {
                $data['error'] = 'File berhasil diupload, tapi tidak ditemukan.';
            }
        } else {
            $data['error'] = 'Gagal menyimpan file ke folder uploads.';
        }
    } else {
        $data['error'] = 'Upload gagal atau file tidak valid.';
    }
} else {
    // Redirect ke index jika bukan POST
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hasil Verifikasi PDF</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { font-size: 18px; margin-bottom: 20px; }
        .result { background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h2>Hasil Verifikasi Dokumen PDF</h2>
    <div class="status"><?= htmlspecialchars($integrity_status) ?></div>

    <?php if (!empty($data['error'])): ?>
        <div class="error">⚠️ <?= htmlspecialchars($data['error']) ?></div>
    <?php else: ?>
        <div class="result">
            <ul>
                <?php foreach ($data as $label => $value): ?>
                    <li><strong><?= $label ?>:</strong> <?= htmlspecialchars($value) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <p><a href="index.php">← Kembali ke halaman upload</a></p>
</body>
</html>
