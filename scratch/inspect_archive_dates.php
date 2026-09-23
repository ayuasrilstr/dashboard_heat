<?php
$files = glob('rpa/engage-rpa/archive/*/*.xlsx');
foreach ($files as $f) {
    echo basename($f) . ": ";
    $content = file_get_contents($f);
    preg_match('/<td>(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $content, $m);
    echo ($m[1] ?? 'no date') . "\n";
}

