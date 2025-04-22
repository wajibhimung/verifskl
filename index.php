<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Tambahan penting -->
    <title>Verifikasi SKL Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #e0f2fe, #f8fafc);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .verify-card {
            background-color: white;
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
        }
        .verify-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .verify-subtitle {
            text-align: center;
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="verify-title">📄 Verifikasi SKL Digital</div>
        <div class="verify-subtitle">
            Unggah dokumen SKL (PDF) untuk memastikan keasliannya secara digital.
        </div>

        <form action="verify.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="pdf_file" class="form-label">Pilih File PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" required>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Verifikasi Dokumen</button>
        </form>

        <div class="text-center text-muted mt-3" style="font-size: 0.85rem;">
            Aplikasi verifikasi SKL – SMKN 1 Telagasari
        </div>
    </div>
</body>
</html>
