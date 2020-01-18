<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

use Exception;
use Psr\Log\LoggerInterface;
use Yogarine\CsvUtils\CsvFile;

class Timelost
{
    public const DEFAULT_MAPPING = [
        'Center'   => 'center',
        'Openings' => 'Openings',
        'Link1'    => 'LINK1',
        'Link2'    => 'Link2',
        'Link3'    => 'Link3',
        'Link4'    => 'Link4',
        'Link5'    => 'Link5',
        'Link6'    => 'Link6',
    ];

    public const OG_MAPPING = [
        'Center'   => 'Big Symbol',
        'Openings' => 'Openings',
        'Link1'    => 'A Combo',
        'Link2'    => 'B Combo',
        'Link3'    => 'C Combo',
        'Link4'    => 'D Combo',
        'Link5'    => 'E Combo',
        'Link6'    => 'F Combo',
    ];

    public const NO_MAPPING = [
        'Center'   => 1,
        'Openings' => 2,
        'Link1'    => 3,
        'Link2'    => 4,
        'Link3'    => 5,
        'Link4'    => 6,
        'Link5'    => 7,
        'Link6'    => 8,
    ];

    public const MAPPING_NAMES = [
        'default' => self::DEFAULT_MAPPING,
        'og'      => self::OG_MAPPING,
        'off'     => self::NO_MAPPING,
    ];

    /**
     * @var int
     */
    public static int $endpointIncrement = 0;

    /**
     * @var Room[]
     */
    public array $entrypoints = [];

    /**
     * @var Room[]
     */
    public array $rooms = [];

    /**
     * @var Link[]
     */
    public array $links = [];

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Options $options
     * @return void
     *
     * @throws Exception
     */
    public function main(Options $options): void
    {
        foreach ($options->input as $file) {
            $csvFile = new CsvFile($file, $options->headerRows);
            $mapping = self::MAPPING_NAMES[$options->mapping];

            foreach ($csvFile as $key => $row) {
                if (!isset($row[$mapping['Link1']])) {
                    // Skip apparently empty lines
                    continue;
                }

                $roomId   = $key + 2;
                $openings = $row[$mapping['Openings']];
                $symbol   = $row[$mapping['Center']];
                $symbol   = $this->normalizeSymbol($symbol);

                if ('B' == $symbol) {
//                    continue;
                }

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

                if (count($openingPositions) > 5) {
                    // Discard opening position information
                    echo "[{$roomId}] Discarding openings: '{$openings}'\n";
                    $openingPositions = [];
                }

                $isEndpoint = false;
                $links = [];
                foreach ($linkCodes as $linkPosition => $linkCode) {
                    $linkCode = $this->normalizeLinkCode($linkCode);
                    $isOpening = isset($openingPositions[$linkPosition]);

                    if (
                        $isOpening &&
                        'BBBBBBB' == $linkCode &&
//                        'B' != $symbol &&
                        ! in_array('CCHSCSS', $linkCodes) &&
                        ! in_array('DTSSSPS', $linkCodes) &&
                        ! in_array('BHSSTBC', $linkCodes)
//                        (
//                            in_array('PDDSCCH', $linkCodes) ||
//                            in_array('DHDSPTT', $linkCodes)
//                        )
                    ) {
                        $linkCode = 'TTTTTT' . self::$endpointIncrement++;
                        $isEndpoint = true;
                        echo "[{$roomId}] Found entrypoint with symbol '{$symbol}'\n";
                    }

                    if ('BBBBBBB' == $linkCode || '' == $linkCode) {
                        continue;
                    }

                    if (!$this->isValidLinkCode($linkCode)) {
                        echo "[{$roomId}] '{$linkCode}' is not a valid link code!\n";
                        continue;
                    }

                    if (!isset($this->links[$linkCode])) {
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
        }

        foreach ($this->entrypoints as $room) {
echo "[{$room->id}] ENTRYPOINT ========\n";
            $grid = [];
            $this->addRoomToGridRecursively($room, $grid);

            ksort($grid);

//            $gridWidth = count($grid);
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
     * @param string $symbol
     * @return string
     */
    public function normalizeSymbol(string $symbol): string
    {
        return strtoupper(substr(trim($symbol), 0, 1));
    }

    /**
     * @param string $linkCode
     * @return string
     */
    public function normalizeLinkCode(string $linkCode): string
    {
        $linkCode = strtoupper($linkCode);
        $linkCode = str_replace('_', 'B', $linkCode);
        if ('BLANK' == $linkCode || preg_match('/^B{1,6}$/', $linkCode)) {
            $linkCode = 'BBBBBBB';
        }
        $linkCode = preg_replace('/[^BCDHPST]/i', '', $linkCode);

        return $linkCode;
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

echo "[{$room->id}]@{$column},{$row} -> {$link->code}({$linkPosition}) -> [{$otherRoom->id}]@{$relativeColumn},{$relativeRow} ";

                if (isset($grid[$relativeColumn][$relativeRow])) {
                    if ($grid[$relativeColumn][$relativeRow]->id != $otherRoom->id) {
echo "overlaps with [{$grid[$relativeColumn][$relativeRow]->id}]@{$relativeColumn},{$relativeRow}\n";
                    } else {
echo "already linked\n";
                    }
                } elseif ($this->isRoomInGrid($otherRoom, $grid)) {
echo "is already in grid\n";
                    continue;
                } else {
echo "linked\n";
                    $otherRoom->matchOrientation($link);

                    if (isset($this->entrypoints[$otherRoom->id])) {
                        echo "[{$otherRoom->id}] ========== REACHED ANOTHER ENTRYPOINT!!! ========";
                    }

                    $this->addRoomToGridRecursively($otherRoom, $grid, $relativeColumn, $relativeRow);
                }
            }
        }
    }

    /**
     * @param Room $room
     * @param array $grid
     * @return bool
     */
    public function isRoomInGrid(Room $room, array $grid): bool
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
    public function getRelativeGridCoordinatesForLinkPosition(int $linkPosition, int $column, int $row): array
    {
        switch ($linkPosition) {
            case 1:
                return [$column + 1, $row - abs($column % 2)];
            case 2:
                return [$column + 1, $row + 1 - abs($column % 2)];
            case 3:
                return [$column,     $row + 1];
            case 4:
                return [$column - 1, $row + 1 - abs($column % 2)];
            case 5:
                return [$column - 1, $row - abs($column % 2)];
            case 6:
                return [$column,     $row -1];
            default:
                throw new Exception("Invalid Link position: '{$linkPosition}'");
        }
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
    public function roomWithLinksAlreadyExists(array $links, $roomId = null): bool
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
