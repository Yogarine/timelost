<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

use Yogarine\CsvUtils\CsvFile;

class Timelost
{
    /**
     * @var Room[]
     */
    public $endpoints = [];

    /**
     * @var Room[]
     */
    public $rooms = [];

    /**
     * @var Link[]
     */
    public $links = [];

    public function main($argv, $argc)
    {
        $csvFile = new CsvFile($argv[1]);

        foreach ($csvFile as $row) {
            if (! $row['Link1']) {
                continue;
            }

            $symbol   = $row['Center'];
            $openings = $row['Openings'];

            $linkCodes = [
                1 => $row['Link1'],
                2 => $row['Link2'],
                3 => $row['Link3'],
                4 => $row['Link4'],
                5 => $row['Link5'],
                6 => $row['Link6'],
            ];

            $isEndpoint = false;

            $links = [];
            foreach ($linkCodes as $key => $linkCode) {
                $linkCode = strtoupper(trim($linkCode));
                $isOpening = false !== strpos($openings, $key);
                if (
                    $isOpening && (
                        'BBBBBBB' == $linkCode ||
                        'BLANK'   == $linkCode
                    )
                ) {
                    $isEndpoint = true;
                }

                if ('BBBBBBB' == $linkCode || '' == $linkCode) {
                    continue;
                }

                if (! $this->isValidLinkCode($linkCode)) {
                    echo "'{$linkCode}' is not a valid link code!\n";
                    continue;
                }

                if (! isset($this->links[$linkCode])) {
                    $this->links[$linkCode] = new Link($linkCode, $isOpening);
                }

                $links[] = $this->links[$linkCode];
            }

            if (! $this->roomWithLinksAlreadyExists($links)) {
                $room = new Room($symbol, $links);
                $this->rooms[$room->id] = $room;

                if ($isEndpoint) {
                    $this->endpoints[$room->id] = $room;
                }
            }
        }

        $rooms = [];
        $links = [];

        $dot = "digraph sample {\n";
        $dotLinks = $this->processRoomsRecursively($this->rooms[0], $rooms, $links);

        foreach ($rooms as $room) {
            $dot .= "    room{$room->id} [shape=box,label=\"{$room->symbol}\"];\n";
        }

        foreach ($links as $link) {
            $dot .= "    link{$link->code} [label=\"{$link->code}\"];\n";
        }

        $dot .= $dotLinks;
        $dot .= "}\n";

        file_put_contents($argv[2], $dot);
    }

    public function processRoomsRecursively(Room $currentRoom, &$rooms, &$links)
    {
        if (isset($rooms[$currentRoom->id])) {
            return '';
        }

        $rooms[$currentRoom->id] = $currentRoom;
        $dot = '';

        foreach ($currentRoom->links as $link) {
            if (isset($links[$link->code])) {
                continue;
            }

            $links[$link->code] = $link;
            $dot .= "    room{$currentRoom->id} -> link{$link->code};\n";

            foreach ($link->rooms as $room) {
                $dot .= $this->processRoomsRecursively($room, $rooms, $links);
            }
        }

        return $dot;
    }

    /**
     * @param string $linkCode
     * @return bool
     */
    public function isValidLinkCode(string $linkCode): bool
    {
        return preg_match('/^[BCDHPST]{7}$/i', $linkCode) != false;
    }

    /**
     * @param Link[] $links
     * @return bool
     */
    public function roomWithLinksAlreadyExists(array $links)
    {
        foreach ($this->rooms as $room) {
            $matchingLinks = $room->matchLinks($links);
            if (count($matchingLinks) > 1) {
//echo "room got a duplicate: " . implode(',', $room->linkCodes()) . PHP_EOL;
                return true;
            }
        }

        return false;
    }
}
