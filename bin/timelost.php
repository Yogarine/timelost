<?php

declare(strict_types=1);

ini_set('memory_limit', '4G');

require_once __DIR__ . '/../vendor/autoload.php';

$timelost = new Yogarine\Timelost\Timelost();
$timelost->main($argv, $argc);
