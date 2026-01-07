<?php
// /HelpDesk_EQF/modules/dashboard/tasks/helpers/TaskFiles.php

function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function safeFilename(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    $name = trim($name, '._-');
    return $name !== '' ? $name : 'file';
}

function normalizeFilesArray(array $files): array {
    // convierte $_FILES['x'] multiple a lista de items
    $out = [];
    if (!isset($files['name']) || !is_array($files['name'])) return $out;

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i] ?? null,
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i] ?? null,
        ];
    }
    return $out;
}

function saveUploadedFiles(array $files, string $destDir, array $allowedExt, int $maxBytes = 15_000_000): array
{
    ensureDir($destDir);

    $saved = [];
    $items = normalizeFilesArray($files);

    foreach ($items as $f) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Error al subir archivo.");
        }

        $original = (string)($f['name'] ?? '');
        $size = (int)($f['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException("Archivo inválido o excede tamaño permitido.");
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            throw new RuntimeException("Tipo de archivo no permitido: .$ext");
        }

        $base = safeFilename(pathinfo($original, PATHINFO_FILENAME));
        $stored = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

        $dest = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $stored;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            throw new RuntimeException("No se pudo guardar el archivo.");
        }

        $saved[] = [
            'original_name' => $original,
            'stored_name' => $stored,
            'mime' => (string)($f['type'] ?? ''),
            'size_bytes' => $size,
        ];
    }

    return $saved;
}
