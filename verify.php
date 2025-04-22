<?php
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
                    $integrity_status = '✅ Dokumen LULUS VERIFIKASI dan BELUM mengalami perubahan.';
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
