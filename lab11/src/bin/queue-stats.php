<?php
require dirname(__DIR__) . '/app/bootstrap.php';
foreach (Queue::NAMES as $name) {
    $queue = new Queue($name);
    echo "$name " . json_encode($queue->stats(), JSON_THROW_ON_ERROR) . PHP_EOL;
    foreach (['ready','working','dead'] as $list) {
        if (in_array("--$list", $argv, true)) {
            foreach ($queue->inspect($list) as $raw) {
                echo "$list $raw\n";
            }
        }
    }
}
