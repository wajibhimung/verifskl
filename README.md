# 🔏 Digital PDF Signature Verifier

A simple PHP-based tool to verify the authenticity of digitally signed PDF documents. It checks for valid digital signatures and displays signer details and certificate validation status.

---

## 🧰 Features

- Upload PDF documents for signature verification
- Detects digital signature integrity and validity
- Displays:
  - Signer's Name
  - Signing Time
  - Signature Validation
  - Certificate Status
  - Location and Reason (if available)
- Responsive interface for mobile and desktop

---

## 📂 Directory Structure

```
verifskl/
├── index.php
├── verify.php
├── uploads/              ← Stores uploaded PDF files temporarily
└── README.md
```

---

## 💡 Server Installation Guide (Debian 11/12)

### 1. Update & Install Dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php php-cli libpoppler-cpp-dev ghostscript -y
```

### 2. Install `pdfsig` (part of poppler-utils)

```bash
sudo apt install poppler-utils -y
```

### 3. Deploy the Application

```bash
sudo mkdir -p /var/www/html/verifskl
sudo chown -R www-data:www-data /var/www/html/verifskl
```

Place the PHP source files (`index.php`, `verify.php`, etc.) in `/var/www/html/verifskl`.

### 4. Apache Configuration (Optional)

Create a virtual host file if needed:

```bash
sudo nano /etc/apache2/sites-available/verifskl.conf
```

Example configuration:

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html/verifskl>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/verifskl_error.log
    CustomLog ${APACHE_LOG_DIR}/verifskl_access.log combined
</VirtualHost>
```

Then enable and reload:

```bash
sudo a2ensite verifskl.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## ✅ How to Use

1. Open the verification page in your browser.
2. Upload a digitally signed PDF.
3. View the results and signature verification status.

---

## 📄 License

MIT License