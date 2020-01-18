<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Room
{
    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $symbol;

    /**
     * @var Link[]
     */
    public array $links;

    /**
     * @param int $id
     * @param string $symbol
     * @param Link[] $links
     */
    public function __construct(int $id, string $symbol, array $links)
    {
        $symbol       = $this->parseSymbol($symbol);
        $this->symbol = strtolower(trim($symbol));
        $this->links  = $links;
        $this->id     = $id;

        foreach ($this->links as $link) {
            if (count($link->rooms) < 2) {
                $link->rooms[$this->id] = $this;
            } else {
//                echo "[{$id}] Too many rooms for link '{$link->code}'\n";
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->links as $link) {
            unset($link->rooms[$this->id]);
        }
    }

    /**
     * @param int $amount
     * @return void
     */
    public function rotate(int $amount): void
    {
        if (0 == $amount) {
            return;
        }

        $newLinks = [];

        foreach ($this->links as $position => $link) {
            $newPosition = $this->rotatePosition($position, $amount);
            $newLinks[$newPosition] = $link;
        }

        $this->links = $newLinks;
    }

    /**
     * @param int $position
     * @param int $amount
     * @return int
     */
    public function rotatePosition(int $position, int $amount): int
    {
        if (0 == $amount) {
            return $position;
        }

        $newPosition = $position + $amount;

        if ($newPosition > 6) {
            $newPosition -= 6;
        } elseif ($newPosition < 1) {
            $newPosition += 6;
        }

        return $newPosition;
    }

    /**
     * @return void
     */
    public function ensureCorrectOrientation(): void
    {
        foreach ($this->links as $link) {
            $this->matchOrientation($link);
        }
    }

    /**
     * @param Link $link
     * @return void
     */
    public function matchOrientation(Link $link): void
    {
        $otherRoom = $link->getOtherRoom($this);

        if ($otherRoom) {
            $position             = $this->getLinkPosition($link);
            $matchingLinkPosition = $otherRoom->getLinkPosition($link);
            $referencePosition    = $this->rotatePosition($matchingLinkPosition, 3);
            $diff                 = $referencePosition - $position;

            if (0 != $diff) {
// TODO add this verbose only
echo "[{$this->id}] Rotating Room by {$diff} ({$position} -> {$referencePosition}) to align it with Room [{$otherRoom->id}]'s Link at position {$matchingLinkPosition}\n";
                $this->rotate($diff);
            }
        }
    }

    /**
     * @param Link $link
     * @return int|null
     */
    public function getLinkPosition(Link $link): ?int
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

    /**
     * @param string $symbol
     * @return string
     */
    public function parseSymbol(string $symbol): string
    {
        return strtoupper(substr($symbol, 0, 1));
    }

    /**
     * @return string[]
     */
    public function getLinkCodes(): array
    {
        $linkCodes = [];
        foreach ($this->links as $link) {
            $linkCodes[] = $link->code;
        }

        return $linkCodes;
    }
}
