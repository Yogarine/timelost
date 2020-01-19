<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Room
{
    /**
     * @var string
     */
    public string $identifier;

    /**
     * @var string
     */
    public string $symbol;

    /**
     * @var Link[]
     */
    public array $links;

    /**
     * @var int[]
     */
    public array $openings;

    /**
     * @param string $identifier
     * @param string $symbol
     * @param Link[] $links
     * @param int[]  $openings
     */
    public function __construct(string $identifier, string $symbol, array $links, array $openings)
    {
        $symbol           = Timelost::normalizeSymbol($symbol);
        $this->symbol     = strtolower(trim($symbol));
        $this->links      = $links;
        $this->identifier = $identifier;
        $this->openings   = $openings;

        foreach ($this->links as $position => $link) {
            $link->rooms[$this->identifier] = $this;

            if (count($link->rooms) > 2) {
                echo "[{$identifier}] Conflicting rooms for link '{$link->code}'\n";
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->links as $link) {
            unset($link->rooms[$this->identifier]);
        }
    }

    public function verifyLinks()
    {
        foreach ($this->links as $position => $link) {
            foreach ($link->rooms as $otherRoom) {
                if ($otherRoom->identifier == $this->identifier) {
                    continue;
                }

                $otherPosition = $otherRoom->getLinkPosition($link);


            }
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
echo "[{$this->identifier}] Rotating Room by {$diff} ({$position} -> {$referencePosition}) to align it with Room [{$otherRoom->identifier}]'s Link at position {$matchingLinkPosition}\n";
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
            if (! $thisLink->isBlank()) {
                foreach ($links as $linkPosition => $link) {
                    if ($thisLink->code == $link->code) {
                        $matchingLinks[$thisLinkPosition] = $thisLink;
                    }
                }
            }
        }

        return $matchingLinks;
    }

    /**
     * @param Link[] $links
     * @return string[]
     */
    public function getMatchingLinkCodes(array $links): array
    {
        $links = $this->getMatchingLinks($links);

        $linkCodes = [];
        foreach ($links as $link) {
            $linkCodes[] = $link->code;
        }

        return $linkCodes;
    }

    /**
     * @return bool
     */
    public function hasEntrypoint(): bool
    {
        foreach ($this->links as $link) {
            if ($link->isEntrypoint()) {
                return true;
            }
        }

        return false;
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
