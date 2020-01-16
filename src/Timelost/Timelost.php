<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

use Exception;
use Yogarine\CsvUtils\CsvFile;

class Timelost
{
    /**
     * @var int
     */
    public static $endpointIncrement = 0;

    /**
     * @var Room[]
     */
    public $entrypoints = [];

    /**
     * @var Room[]
     */
    public $rooms = [];

    /**
     * @var Link[]
     */
    public $links = [];

    /**
     * @param string[] $argv
     * @param int      $argc
     * @throws Exception
     */
    public function main($argv, $argc)
    {
        $csvFile = new CsvFile($argv[1]);

        foreach ($csvFile as $row) {
            if (! isset($row['Link1']) && ! isset($row['A Combo'])) {
                // Skip apparently empty lines
                continue;
            }

            $symbol = isset($row['Center']) ? $row['Center'] : $row['Big Symbol'];
            $symbol = strtoupper(substr($symbol, 0, 1));

            $openings = $row['Openings'];

            $linkCodes = [
                1 => isset($row['Link1']) ? $row['Link1'] : $row['A Combo'],
                2 => isset($row['Link2']) ? $row['Link2'] : $row['B Combo'],
                3 => isset($row['Link3']) ? $row['Link3'] : $row['C Combo'],
                4 => isset($row['Link4']) ? $row['Link4'] : $row['D Combo'],
                5 => isset($row['Link5']) ? $row['Link5'] : $row['E Combo'],
                6 => isset($row['Link6']) ? $row['Link6'] : $row['F Combo'],
            ];

            $isEndpoint = false;

            $links = [];
            foreach ($linkCodes as $linkPosition => $linkCode) {
                // Do some normalizing
                $linkCode = str_replace(' ', '', $linkCode);
                $linkCode = str_replace('_', 'B', $linkCode);
                $linkCode = strtoupper(trim($linkCode));

                $isOpening = false !== strpos($openings, (string) $linkPosition);

                if (
                    $isOpening && (
                        'BBBBBBB' == $linkCode ||
                        'BLANK'   == $linkCode
                    )
                ) {
                    $linkCode   = 'TTTTTT' . self::$endpointIncrement++;
                    $isEndpoint = true;
                }

                if ('BBBBBBB' == $linkCode || 'BLANK'   == $linkCode || '' == $linkCode) {
                    continue;
                }

                if (! $this->isValidLinkCode($linkCode)) {
                    echo "'{$linkCode}' is not a valid link code!\n";
                    continue;
                }

                if (! isset($this->links[$linkCode])) {
                    $this->links[$linkCode] = new Link($linkCode, $isOpening);
                }

                $links[$linkPosition] = $this->links[$linkCode];
            }

            if (! $this->roomWithLinksAlreadyExists($links)) {
                $room = new Room($symbol, $links);
                $this->rooms[$room->id] = $room;

                if ($isEndpoint) {
                    $this->entrypoints[$room->id] = $room;
                }
            }
        }

        unset($csvFile);

        foreach ($this->entrypoints as $room) {
            $grid = [];
            $this->addRoomToGridRecursively($room, $grid);

            ksort($grid);

            foreach ($grid as $column => &$data) {
                ksort($data);
            }
        }
    }

    /**
     * @param Room $room
     * @param array $grid
     * @param int $row
     * @param int $column
     * @return void
     *
     * @throws Exception
     */
    public function addRoomToGridRecursively(Room $room, array &$grid = [], int $row = 0, int $column = 0): void
    {
        if (isset($grid[$column][$row])) {
            if ($grid[$column][$row]->id != $room->id) {
                throw new Exception("Got room inconsistency!");
            } else {
                return;
            }
        }
        $grid[$column][$row] = $room;

        foreach ($room->links as $linkPosition => $link) {
            $otherRoom = $link->getOtherRoom($room);
            if ($otherRoom) {
                [$relativeRow, $relativeColumn] = $this->getRelativeGridCoordinatesForLinkPosition($linkPosition, $row, $column);
                $this->addRoomToGridRecursively($otherRoom, $grid, $relativeRow, $relativeColumn);
            }
        }
    }

    /**
     * @param int $linkPosition
     * @param int $row
     * @param int $column
     * @return array
     * @throws Exception
     */
    public function getRelativeGridCoordinatesForLinkPosition(int $linkPosition, int $row, int $column)
    {
        switch ($linkPosition) {
            case 1:
                return [$row - ($column % 2), $column + 1];
            case 2:
                return [$row + 1 - ($column % 2), $column + 1];
            case 3:
                return [$row + 1, $column];
            case 4:
                return [$row + 1 - ($column % 2), $column -1];
            case 5:
                return [$row - ($column % 2), $column - 1];
            case 6:
                return [$row -1, $column];
            default:
                throw new Exception("Invalid Link position: '{$linkPosition}'");
        }
    }

    /**
     * @deprecated
     * @param string $file
     * @return void
     */
    public function createDot(string $file): void
    {
        $rooms = [];
        $links = [];

        $dot = "digraph sample {\n";
        $dotLinks = $this->createDotRecursively(reset($this->entrypoints), $rooms, $links);

        foreach ($links as $link) {
            $dot .= "    link{$link->code} [label=\"{$link->code}\"];\n";
        }

        foreach ($rooms as $room) {
            $dot .= "    room{$room->id} [shape=box,label=\"{$room->symbol}\"];\n";
        }

        $dot .= $dotLinks;
        $dot .= "}\n";

        file_put_contents($file, $dot);
    }

    /**
     * @deprecated
     * @param Room $currentRoom
     * @param Room[] $rooms
     * @param Link[] $links
     * @return string
     */
    public function createDotRecursively(Room $currentRoom, array &$rooms, array &$links)
    {
        $rooms[$currentRoom->id] = $currentRoom;
        $dot = '';

        foreach ($currentRoom->links as $link) {
            $links[$link->code] = $link;
            $dot .= "    room{$currentRoom->id} -> link{$link->code}";
            $otherRoom = $link->getOtherRoom($currentRoom);
            if ($otherRoom && ! isset($rooms[$otherRoom->id])) {
                $dot .= " -> room{$otherRoom->id}";
            }
            $dot .= ";\n";

            foreach ($link->rooms as $room) {
                if (isset($rooms[$room->id])) {
                    continue;
                }

                $dot .= $this->createDotRecursively($room, $rooms, $links);
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
        return preg_match('/^[BCDHPST01]{7}$/i', $linkCode) != false;
    }

    /**
     * @param Link[] $links
     * @return bool
     */
    public function roomWithLinksAlreadyExists(array $links)
    {
        foreach ($this->rooms as $room) {
            $matchingLinks = $room->getMatchingLinks($links);
            if (count($matchingLinks) > 1) {
//echo "room got a duplicate: " . implode(',', $room->linkCodes()) . PHP_EOL;
                return true;
            }
        }

        return false;
    }
}
