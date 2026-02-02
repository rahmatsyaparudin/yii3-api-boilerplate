<?php

$source = __DIR__ . '/vendor/rahmatsyaparudin/yii3-api-boilerplate/scripts';
$destination = __DIR__ . '/scripts';

// 1. Cek & Buat folder tujuan jika belum ada
if (!is_dir($destination)) {
    mkdir($destination, 0755, true);
    echo "Folder 'scripts' berhasil dibuat.\n";
}

// 2. Fungsi helper untuk copy folder secara recursive
function copyRecursive(string $src, string $dst): void 
{
    $dir = opendir($src);
    @mkdir($dst);
    
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
 
        $srcFile = $src . '/' . $file;
        $dstFile = $dst . '/' . $file;

        if (is_dir($srcFile)) {
            copyRecursive($srcFile, $dstFile);
        } else {
            // copy() akan otomatis menimpa (replace) file jika sudah ada
            copy($srcFile, $dstFile);
        }
    }
    closedir($dir);
}

// 3. Jalankan proses copy
if (is_dir($source)) {
    copyRecursive($source, $destination);
    echo "Isi folder 'scripts' telah diperbarui (replaced).\n";
} else {
    echo "Error: Source folder tidak ditemukan di vendor.\n";
}