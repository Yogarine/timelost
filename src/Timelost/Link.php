<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Link
{
    /**
     * @var string
     */
    public string $code;

    /**
     * @var bool
     */
    public bool $isOpening;

    /**
     * @var Room[]
     */
    public array $rooms = [];

    /**
     * @param string $code
     * @param bool   $isOpening
     */
    public function __construct(string $code, bool $isOpening)
    {
        $this->code      = strtoupper($code);
        $this->isOpening = $isOpening;
    }

    /**
     * @param Room $room
     * @return Room|null
     */
    public function getOtherRoom(Room $room): ?Room
    {
        foreach ($this->rooms as $otherRoom) {
            if ($otherRoom->id != $room->id) {
                return $otherRoom;
            }
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function getRoomIds(): array
    {
        $roomIds = [];

        foreach ($this->rooms as $room) {
            $roomIds[] = $room->id;
        }

        return $roomIds;
    }
}
