<?php

declare(strict_types=1);

use Yogarine\CsvUtils\CsvFile;

require_once __DIR__ . '/../vendor/autoload.php';

$timelost = new Yogarine\Timelost\Timelost();
$timelost->main($argv, $argc);
