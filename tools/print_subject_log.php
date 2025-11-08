<?php
$tmp = sys_get_temp_dir();
echo "TEMP_DIR: " . $tmp . PHP_EOL;
$f = $tmp . DIRECTORY_SEPARATOR . 'subject_add.log';
if (file_exists($f)) {
    echo "LOG_PATH: " . $f . PHP_EOL;
    echo "---- LOG START ----" . PHP_EOL;
    echo file_get_contents($f);
    echo PHP_EOL . "---- LOG END ----" . PHP_EOL;
} else {
    echo "NO_LOG_FILE_IN_TEMP" . PHP_EOL;
}
