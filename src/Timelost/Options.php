<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Options
{
    /**
     * @var string[]
     */
    public $input;

    /**
     * @var string
     */
    public $mapping;

    /**
     * @var int
     */
    public $headerRows;

    public function __construct($opt)
    {
        $input = $opt['i'] ?? $opt['input'];
        if (! is_array($input)) {
            $input = [$input];
        }
        $this->input = $input;

        $this->mapping = $opt['m'] ?? $opt['mapping'] ?? 'default';
        $this->headerRows = (int) ($opt['h'] ?? $opt['header-rows'] ?? 0);
    }

    public static function fromCommandLineOptions()
    {
        $options = 'i:m:h:';
        $longopts = [
            'input',
            'mapping:',
            'header-rows:',
        ];

        $opt = getopt($options, $longopts);

        return new self($opt);
    }
}
