<?php
$content = file_get_contents('rpa/engage-rpa/downloads/32a_engage.xlsx');
if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $tr_matches)) {
    echo "Found " . count($tr_matches[0]) . " rows\n";
    for ($i = 0; $i < min(5, count($tr_matches[0])); $i++) {
        if (preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $tr_matches[0][$i], $td_matches)) {
            $cols = array_map(function($c) { return trim(strip_tags($c)); }, $td_matches[1]);
            echo "Row $i: " . implode(' | ', array_slice($cols, 0, 15)) . "\n";
        }
    }
} else {
    echo "No table rows found\n";
}
