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
        $symbol       = $this->parseSymbol($symbol);
        $this->symbol = strtolower(trim($symbol));
        $this->links  = $links;
        $this->id     = self::$idIncrement++;

        $this->ensureCorrectOrientation();

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

    public function rotate($amount): void
    {
        $newLinks = [];

        foreach ($this->links as $position => $link) {
            $newPosition = $position + $amount;

            if ($newPosition > 6) {
                $newPosition -= 6;
            }

            $newLinks[$newPosition] = $link;
        }

        $this->links = $newLinks;
    }

    /**
     * @return void
     */
    public function ensureCorrectOrientation(): void
    {
        foreach ($this->links as $position => $link) {
            $otherRoom = $link->getOtherRoom($this);
            if ($otherRoom) {
                $referencePosition = $otherRoom->getMatchingLinkPosition($link);
                $this->rotate($referencePosition - $position);
            }
        }
    }

    /**
     * @param Link $link
     * @return int|null
     */
    public function getMatchingLinkPosition(Link $link): ?int
    {
        $matchingLinks = $this->getMatchingLinks([$link]);
        foreach ($matchingLinks as $position => $link) {
            return $position;
        }

        return null;
    }

    /**
     * @param Link[] $links
     * @return Link[]
     */
    public function getMatchingLinks(array $links): array
    {
        $matchingLinks = [];

        foreach ($this->links as $thisLinkPosition => $thisLink) {
            foreach ($links as $linkPosition => $link) {
                if ($thisLink->code == $link->code) {
                    $matchingLinks[$thisLinkPosition] = $thisLink;
                }
            }
        }

        return $matchingLinks;
    }

    public function parseSymbol(string $symbol)
    {
        return strtoupper(substr($symbol, 0, 1));
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
