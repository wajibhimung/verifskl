<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
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

                $normalized = trim(preg_replace('/\s+/', ' ', $signer_name));
                $trusted_list = array_map(fn($n) => trim(preg_replace('/\s+/', ' ', $n)), $trusted_signers);

                if (in_array($normalized, $trusted_list)) {
                    $data['🔒 Status Sertifikat'] = '✅ OK (Trusted by SMKN 1 Telagasari)';
                }

                if (isset($data['✅ Validasi Tanda Tangan']) && stripos($data['✅ Validasi Tanda Tangan'], 'valid') !== false) {
                    $is_modified = false;
                    $integrity_status = '✅ Dokumen VALID dan BELUM mengalami perubahan.';
                }
            } else {
                $data['error'] = 'Gagal membaca hasil verifikasi dari pdfsig.';
            }
        } else {
            $data['error'] = 'Gagal menyimpan file ke folder uploads.';
        }
    } else {
        $data['error'] = 'Upload gagal atau file tidak valid.';
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Verifikasi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            color: <?= $is_modified ? '#e74c3c' : '#27ae60' ?>;
            margin-bottom: 20px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .back-btn {
            margin-top: 20px;
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
        }
        .back-btn:hover {
            background: #2980b9;
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 15px;
            }
            .status {
                font-size: 16px;
            }
            td {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status"><?= htmlspecialchars($integrity_status) ?></div>
        <?php if (isset($data['error'])): ?>
            <p style="color: red;"><?= htmlspecialchars($data['error']) ?></p>
        <?php else: ?>
            <table>
                <?php foreach ($data as $key => $value): ?>
                    <tr>
                        <td><?= htmlspecialchars($key) ?></td>
                        <td><?= htmlspecialchars($value) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <a href="index.php" class="back-btn">🔙 Kembali</a>
    </div>
</body>
</html>
