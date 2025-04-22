<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$trusted_signers = ['- Dra. Hj. Helwatin Najwa'];
$data = [];
$is_modified = true;
$integrity_status = '❌ Dokumen Telah Mengalami Perubahan atau Tidak Terverifikasi.';
$fileName = '';

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
    <title>Hasil Verifikasi Dokumen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
            margin: 20px;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .status-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 16px;
            word-wrap: break-word;
        }
        .status-valid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-invalid {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .details {
            background-color: #f1f1f1;
            padding: 10px 14px;
            border-radius: 6px;
            line-height: 1.6;
            overflow-wrap: break-word;
        }
        .details div {
            margin-bottom: 8px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media screen and (max-width: 600px) {
            .status-box {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🔍 Hasil Verifikasi Dokumen</h2>

    <?php if (!empty($fileName)): ?>
        <p><strong>📄 Nama File:</strong> <?= htmlspecialchars($fileName) ?></p>
    <?php endif; ?>

    <?php
        $statusClass = $is_modified ? 'status-invalid' : 'status-valid';
        echo '<div class="status-box ' . $statusClass . '">' . htmlspecialchars($integrity_status) . '</div>';
    ?>

    <div class="details">
        <?php
        if (!empty($data['error'])) {
            echo '<div style="color: red;">⚠️ ' . htmlspecialchars($data['error']) . '</div>';
        } else {
            foreach ($data as $label => $value) {
                echo "<div><strong>$label:</strong> " . htmlspecialchars($value) . "</div>";
            }
        }
        ?>
    </div>

    <a class="back-link" href="index.php">← Kembali ke halaman upload</a>
</div>
</body>
</html>
