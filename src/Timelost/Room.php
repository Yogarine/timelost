<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Room
{
    public static $idIncrement = 0;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $symbol;

    /**
     * @var Link[]
     */
    public $links;

    /**
     * Room constructor.
     * @param string $symbol
     * @param Link[] $links
     */
    public function __construct(string $symbol, array $links)
    {
        $symbol = strtolower($symbol);
        $this->symbol = strtolower(trim($symbol));
        $this->links  = $links;
        $this->id     = self::$idIncrement++;

        foreach ($this->links as $link) {
            $link->rooms[$this->id] = $this;
        }
    }

    public function __destruct()
    {
        foreach ($this->links as $link) {
            unset($link->rooms[$this->id]);
        }
    }

    /**
     * @param Link[] $links
     * @return array
     */
    public function matchLinks(array $links): array
    {
        $matchingLinks = [];

        foreach ($this->links as $existingLink) {
            foreach ($links as $link) {
                if ($existingLink->code == $link->code) {
                    $matchingLinks[] = $link;
                }
            }
        }

        return $matchingLinks;
    }

    /**
     * @return string[]
     */
    public function linkCodes(): array
    {
        $linkCodes = [];
        foreach ($this->links as $link) {
            $linkCodes[] = $link->code;
        }

        return $linkCodes;
    }
}
