<?php

$i = 0;
while (true) {
    print $i++ . PHP_EOL;
    sleep(1);

    if ($i > 1000) {
        break;
    }
}
