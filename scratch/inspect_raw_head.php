<?php
$f = 'rpa/engage-rpa/downloads/32a_engage.xlsx';
if (file_exists($f)) {
    echo substr(file_get_contents($f), 0, 1000);
} else {
    echo "Not found\n";
}

