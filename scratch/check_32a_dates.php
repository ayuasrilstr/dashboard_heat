<?php
$content = file_get_contents('rpa/engage-rpa/downloads/32a_engage.xlsx');
preg_match_all('/<td>(\d{4}-\d{2}-\d{2})/', $content, $m);
$counts = array_count_values($m[1]);
print_r($counts);
