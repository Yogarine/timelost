<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Options
{
    /**
     * @var string[]
     */
    public array $input;

    /**
     * @var string
     */
    public string $mapping;

    /**
     * @var int
     */
    public int $headerRows;

    /**
     * @param array $opt
     */
    public function __construct(array $opt)
    {
        $input = $opt['i'] ?? $opt['input'];
        if (! is_array($input)) {
            $input = [$input];
        }
        $this->input = $input;

        $this->mapping = $opt['m'] ?? $opt['mapping'] ?? 'default';
        $this->headerRows = (int) ($opt['h'] ?? $opt['header-rows'] ?? 0);
    }

    public static function fromCommandLineOptions(): self
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
