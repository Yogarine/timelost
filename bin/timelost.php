<?php

declare(strict_types=1);

use Yogarine\Timelost\Options;
use Yogarine\Timelost\Timelost;

require_once __DIR__ . '/../vendor/autoload.php';

$options = Options::fromCommandLineOptions();

if ($argc < 2 || ! $options->input) {
    printUsage($argv, $argc);
    exit(1);
}

$timelost = new Timelost();
$timelost->main($options);

function printUsage(array $argv, int $argc): void
{
    $usage = "Usage: ${argv[0]} [-m=default|og|off] [-h=<rows>] -i=<input>\n";

    echo $usage;
}
