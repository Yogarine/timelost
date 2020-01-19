<?php

namespace Yogarine\Timelost;

class LinkCollection
{
    /**
     * @var Room[]
     */
    public array $entrypoints = [];

    /**
     * @var Room[]
     */
    private array $rooms = [];

    /**
     * @var Link[]
     */
    private array $links = [];

    /**
     * @var LinkCollection
     */
    public ?LinkCollection $blackList;

    public function __construct(?LinkCollection $blackList = null)
    {
        $this->blackList = $blackList;
    }

    /**
     * @param array  $row
     * @param string $identifier
     * @param string $mapping
     * @return void
     */
    public function addRow($row, string $identifier, string $mapping): void
    {
        $mapping = Timelost::MAPPING_NAMES[$mapping];

        if (! isset($row[$mapping['Link1']]) || ! $row[$mapping['Link1']]) {
            // Skip apparently empty lines
            return;
        }

        $openings = $row[$mapping['Openings']];
        $symbol   = $row[$mapping['Center']];
        $symbol   = Timelost::normalizeSymbol($symbol);

        $linkCodes = [
            1 => $row[$mapping['Link1']],
            2 => $row[$mapping['Link2']],
            3 => $row[$mapping['Link3']],
            4 => $row[$mapping['Link4']],
            5 => $row[$mapping['Link5']],
            6 => $row[$mapping['Link6']],
        ];

        $openingPositions = [];
        for ($i = 1; $i < 7; $i++) {
            if (false !== strpos($openings, (string)$i)) {
                $openingPositions[$i] = $i;
            }
        }

        $links = [];
        foreach ($linkCodes as $linkPosition => $linkCode) {
            $isOpening = isset($openingPositions[$linkPosition]);
            $linkCode = Link::normalizeCode($linkCode, $isOpening);

            if (! Timelost::isValidLinkCode($linkCode)) {
                echo "[{$identifier}] '{$linkCode}' is not a valid link code!\n";
                continue;
            }

            $link = new Link($linkCode, $isOpening);
            $this->setLinkIfNotBlank($link);

            $links[$linkPosition] = $this->getLink($linkCode, $isOpening) ?? $link;
        }

        $existingRoom = $this->findExistingRoomWithLinks($links);
        if ($existingRoom) {
            echo "[{$identifier}] Room has more than one dupe link to Room [{$existingRoom->identifier}]: " .
                implode(', ', $existingRoom->getMatchingLinkCodes($links)) . PHP_EOL;
            return;
        } elseif($this->blackList) {
            $existingRoom = $this->blackList->findExistingRoomWithLinks($links);

            if ($existingRoom && 'B' != $existingRoom->symbol) {
                echo "[{$identifier}] Disregard Phase 1 room: "  .
                    implode(', ', $existingRoom->getMatchingLinkCodes($links)) . PHP_EOL;
                return;
            }
        }

        $this->rooms[$identifier] = new Room($identifier, $symbol, $links, $openingPositions);

        if ($this->rooms[$identifier]->hasEntrypoint()) {
            echo "[$identifier] Found entrypoint.\n";
            $this->entrypoints[$identifier] = $this->rooms[$identifier];
        }
    }

    /**
     * @param string $linkCode
     * @param bool   $isOpening
     * @return Link|null
     */
    public function getLink(string $linkCode, bool $isOpening): ?Link
    {
        return $this->links[($isOpening ? '1' : '0') . $linkCode] ?? null;
    }

    /**
     * @param Link $link
     * @return void
     */
    public function setLinkIfNotBlank(Link $link): void
    {
        $linkIdentifier = ($link->isOpening ? '1' : '0') . $link->code;

        if (! isset($this->links[$linkIdentifier]) && ! $link->isBlank()) {
            $this->links[$linkIdentifier] = $link;
        }
    }

    /**
     * @param Link[] $links
     * @return Room|null
     */
    public function findExistingRoomWithLinks(array $links): ?Room
    {
        foreach ($this->rooms as $room) {
            $matchingLinks = $room->getMatchingLinks($links);
            if (count($matchingLinks) > 1) {
                $matchingLinkCodes = [];
                foreach ($matchingLinks as $matchingLink) {
                    $matchingLinkCodes[] = $matchingLink->code;
                }
                return $room;
            }
        }

        return null;
    }
}
