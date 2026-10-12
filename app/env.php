<?php

function load_env(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException(".env file not found at: $path");
    }

    $first = true;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        // Windows editörleri dosyanın başına BOM ekleyebiliyor; ilk anahtarı bozar.
        if ($first) {
            $line  = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $first = false;
        }

        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
    }
}
