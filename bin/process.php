<?php

require('vendor/autoload.php');

$processor = new Aeronautics\Mustang\Processor($argv[1], glob($argv[2]));
file_put_contents(
    str_replace('/data/cssl', '/data/results', $argv[1]),
    $processor->process()
);
