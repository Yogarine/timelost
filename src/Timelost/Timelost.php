<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

use Exception;
use Yogarine\CsvUtils\CsvFile;

class Timelost
{
    const DEFAULT_MAPPING = [
        'Center'   => 'Center',
        'Openings' => 'Openings',
        'Link1'    => 'LINK1',
        'Link2'    => 'Link2',
        'Link3'    => 'Link3',
        'Link4'    => 'Link4',
        'Link5'    => 'Link5',
        'Link6'    => 'Link6',
    ];

    const OG_MAPPING = [
        'Center'   => 'Big Symbol',
        'Openings' => 'Openings',
        'Link1'    => 'A Combo',
        'Link2'    => 'B Combo',
        'Link3'    => 'C Combo',
        'Link4'    => 'D Combo',
        'Link5'    => 'E Combo',
        'Link6'    => 'F Combo',
    ];

    const NO_MAPPING = [
        'Center'   => 1,
        'Openings' => 2,
        'Link1'    => 3,
        'Link2'    => 4,
        'Link3'    => 5,
        'Link4'    => 6,
        'Link5'    => 7,
        'Link6'    => 8,
    ];

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
     * @param array    $mapping
     * @throws Exception
     */
    public function main($argv, $argc, $mapping = self::DEFAULT_MAPPING)
    {
        $csvFile = new CsvFile($argv[1]);

        foreach ($csvFile as $key => $row) {
            if (! isset($row[$mapping['Link1']])) {
                // Skip apparently empty lines
                continue;
            }

            $roomId    = $key + 2;
            $openings  = $row[$mapping['Openings']];
            $symbol    = $row[$mapping['Center']];
            $symbol    = strtoupper(substr($symbol, 0, 1));
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
                if (false !== strpos($openings, (string) $i)) {
                    $openingPositions[$i] = $i;
                }
            }

            if (count($openingPositions) > 5) {
                // Discard opening position information
echo "[{$roomId}] Discarding openings: '{$openings}'\n";
                $openingPositions = [];
            }

            $isEndpoint = false;
            $links = [];
            foreach ($linkCodes as $linkPosition => $linkCode) {
                // Do some normalizing
                $linkCode = preg_replace('/[^BCDHPST]/', '', $linkCode);
                $linkCode = strtoupper($linkCode);

                $isOpening = isset($openingPositions[$linkPosition]);

                if (
                    $isOpening && (
//                        in_array('PDDSCCH', $linkCodes) ||
                        in_array('DHDSPTT', $linkCodes)
                    )
                ) {
                    $linkCode   = 'TTTTTT' . self::$endpointIncrement++;
                    $isEndpoint = true;
                }

                if ('BBBBBBB' == $linkCode || 'BLANK'   == $linkCode || '' == $linkCode) {
                    continue;
                }

                if (! $this->isValidLinkCode($linkCode)) {
                    echo "[{$roomId}] '{$linkCode}' is not a valid link code!\n";
                    continue;
                }

                if (! isset($this->links[$linkCode])) {
                    $this->links[$linkCode] = new Link($linkCode, $isOpening);
                }

                $links[$linkPosition] = $this->links[$linkCode];
            }

            if (! $this->roomWithLinksAlreadyExists($links, $roomId)) {
                $room = new Room($roomId, $symbol, $links);
                $this->rooms[$room->id] = $room;

                if ($isEndpoint) {
                    $this->entrypoints[$room->id] = $room;
                }
            }
        }

        $csvFile = null;
        unset($csvFile);

        $this->rooms = null;
        unset($this->rooms);
        $this->links = null;
        unset($this->links);

        foreach ($this->entrypoints as $room) {
            $grid = [];
            $this->addRoomToGridRecursively($room, $grid);

            ksort($grid);

            $gridWidth = count($grid);
            $gridHeight = 0;

            foreach ($grid as $column => &$data) {
                $columnHeight = count($data);
                if ($columnHeight > $gridHeight) {
                    $gridHeight = $columnHeight;
                }

                ksort($data);
            }

//            $image = imagecreatetruecolor(($gridWidth * 100) + 200, ($gridHeight * 100) + 200);

//            imagepolygon();
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
    public function addRoomToGridRecursively(Room $room, array &$grid, int $column = 0, int $row = 0): void
    {
        $grid[$column][$row] = $room;

        foreach ($room->links as $linkPosition => &$link) {
            $otherRoom = $link->getOtherRoom($room);
            if ($otherRoom) {
                [$relativeColumn, $relativeRow] = $this->getRelativeGridCoordinatesForLinkPosition($linkPosition, $column, $row);

                if (isset($grid[$relativeColumn][$relativeRow])) {
                    if ($grid[$relativeColumn][$relativeRow]->id != $otherRoom->id) {
echo "[{$otherRoom->id}] Room's relative position ({$relativeColumn}, {$relativeRow}) overlaps with Room [{$grid[$relativeColumn][$relativeRow]->id}]\n";
                    }
                } elseif ($this->isRoomInGrid($otherRoom, $grid)) {
echo "[{$otherRoom->id}] Room is already in grid\n";
                    continue;
                } else {
                    $this->addRoomToGridRecursively($otherRoom, $grid, $relativeColumn, $relativeRow);
                }
            }
        }
    }

    public function isRoomInGrid(Room $room, array $grid)
    {
        foreach ($grid as $column) {
            foreach ($column as $row) {
                if ($row->id == $room->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int $linkPosition
     * @param int $row
     * @param int $column
     * @return array
     * @throws Exception
     */
    public function getRelativeGridCoordinatesForLinkPosition(int $linkPosition, int $column, int $row)
    {
        switch ($linkPosition) {
            case 1:
                return [$column + 1, $row - ($column % 2)];
            case 2:
                return [$column + 1, $row + 1 - ($column % 2)];
            case 3:
                return [$column,     $row + 1];
            case 4:
                return [$column - 1, $row + 1 - ($column % 2)];
            case 5:
                return [$column - 1, $row - ($column % 2)];
            case 6:
                return [$column,     $row -1];
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
        return preg_match('/^([BCDHPST]{7}|T{6}\d)$/i', $linkCode) != false;
    }

    /**
     * @param Link[] $links
     * @param null $roomId
     * @return bool
     */
    public function roomWithLinksAlreadyExists(array $links, $roomId = null)
    {
        foreach ($this->rooms as $room) {
            $matchingLinks = $room->getMatchingLinks($links);
            if (count($matchingLinks) > 1) {
                $matchingLinkCodes = [];
                foreach ($matchingLinks as $matchingLink) {
                    $matchingLinkCodes[] = $matchingLink->code;
                }
echo "[{$roomId}] Room has more than one dupe link to Room [{$room->id}]: " . implode(', ', $matchingLinkCodes) . PHP_EOL;
                return true;
            }
        }

        return false;
    }
}
