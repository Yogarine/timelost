<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

use Exception;
use Psr\Log\LoggerInterface;
use Yogarine\CsvUtils\CsvFile;

class Timelost
{
    public const DEFAULT_MAPPING = [
        'Center'   => 'Center',
        'Openings' => 'Openings',
        'Link1'    => 'Link1',
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
        'legacy'  => self::OG_MAPPING,
        'off'     => self::NO_MAPPING,
    ];

    /**
     * @var LinkCollection
     */
    public LinkCollection $legacyCollection;

    /**
     * @var LinkCollection
     */
    public LinkCollection $collection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $linkCode
     * @return bool
     */
    public static function isValidLinkCode(string $linkCode): bool
    {
        return preg_match('/^[BCDHPST]{7}$/i', $linkCode) != false;
    }

    /**
     * @param Options $options
     * @return void
     *
     * @throws Exception
     */
    public function main(Options $options): void
    {
        $this->legacyCollection = new LinkCollection();
        $legacyCsvFile = new CsvFile(__DIR__ . '/../../test/path_of_time_1.csv', $options->headerRows);
        foreach ($legacyCsvFile as $key => $row) {
            $roomId = "phase1:" . ($key + 2);
            $this->legacyCollection->addRow($row, $roomId, 'legacy');
        }

        $this->collection = new LinkCollection($this->legacyCollection);
        foreach ($options->input as $inputKey => $file) {
            $csvFile = new CsvFile($file, $options->headerRows);

            foreach ($csvFile as $key => $row) {
                $roomId = "{$inputKey}:" . ($key + 2);
                $this->collection->addRow($row, $roomId, $options->mapping);
            }
        }

        foreach ($this->collection->entrypoints as $room) {
echo "[{$room->identifier}] ENTRYPOINT ========\n";
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

            die();

//            $image = imagecreatetruecolor(($gridWidth * 100) + 200, ($gridHeight * 100) + 200);

//            imagepolygon();
        }
    }

    /**
     * @param string $symbol
     * @return string
     */
    public static function normalizeSymbol(string $symbol): string
    {
        return strtoupper(substr(trim($symbol), 0, 1));
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
                [$relativeColumn, $relativeRow] = $this->getRelativeGridCoordinatesForLinkPosition(
                    $linkPosition,
                    $column,
                    $row
                );

                echo "[{$room->identifier}]@{$column},{$row} -> {$link->code}({$linkPosition}) -> " .
                     "[{$otherRoom->identifier}]@{$relativeColumn},{$relativeRow} ";

                if (isset($grid[$relativeColumn][$relativeRow])) {
                    if ($grid[$relativeColumn][$relativeRow]->identifier != $otherRoom->identifier) {
                        echo "overlaps with [{$grid[$relativeColumn][$relativeRow]->identifier}]" .
                             "@{$relativeColumn},{$relativeRow}\n";
                    } else {
                        echo "already linked\n";
                    }
                } elseif ($this->isRoomInGrid($otherRoom, $grid)) {
                    echo "is already in grid\n";
                } else {
                    echo "linked\n";
                    $otherRoom->matchOrientation($link);

                    if (isset($this->entrypoints[$otherRoom->identifier])) {
                        echo "[{$otherRoom->identifier}] ========== REACHED ANOTHER ENTRYPOINT!!! ========\n";
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
        /**
         * @var Room[] $column
         */
        foreach ($grid as $column) {
            foreach ($column as $row) {
                if ($row->identifier == $room->identifier) {
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
}
