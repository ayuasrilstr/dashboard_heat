<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($it as $file) {
    if ($file->isFile() && strpos($file->getPathname(), '.git') === false) {
        if ($file->getMTime() > strtotime('2026-09-14 00:00:00')) {
            echo date('Y-m-d H:i:s', $file->getMTime()) . ' ' . $file->getPathname() . PHP_EOL;
        }
    }
}

