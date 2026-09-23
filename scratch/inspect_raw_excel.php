<?php
$file = __DIR__ . '/../rpa/engage-rpa/downloads/32a_engage.xlsx';
if (!file_exists($file)) {
    echo "File not found: $file\n";
    // Check archive
    $files = glob(__DIR__ . '/../rpa/engage-rpa/archive/*/*.xlsx');
    if ($files) $file = end($files);
}
echo "Inspecting: $file\n";

$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
    $sharedStrings = [];
    if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($idx));
        foreach ($xml->si as $si) {
            $sharedStrings[] = (string) $si->t;
        }
    }
    
    if (($idx = $zip->locateName('xl/worksheets/sheet1.xml')) !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($idx));
        $rowCount = 0;
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $v = (string) $c->v;
                $t = (string) $c['t'];
                if ($t === 's' && isset($sharedStrings[$v])) {
                    $val = $sharedStrings[$v];
                } else {
                    $val = $v;
                }
                $rowData[(string)$c['r']] = $val;
            }
            if ($rowCount < 5) {
                print_r($rowData);
            }
            $rowCount++;
        }
        echo "Total rows: $rowCount\n";
    }
    $zip->close();
}

