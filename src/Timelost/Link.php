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
        $this->code      = self::normalizeCode($code, $isOpening);
        $this->isOpening = $isOpening;
    }

    /**
     * @param string $linkCode
     * @param bool   $isOpening
     * @return string
     */
    public static function normalizeCode(string $linkCode, bool $isOpening): string
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
     * @return bool
     */
    public function isEntrypoint(): bool
    {
        return $this->isOpening && $this->isBlank();
    }

    public function isWall(): bool
    {
        return ! $this->isOpening && $this->isBlank();
    }

    /**
     * @return bool
     */
    public function isBlank(): bool
    {
        return 'BBBBBBB' == $this->code;
    }

    /**
     * @param Room $room
     * @return Room|null
     */
    public function getOtherRoom(Room $room): ?Room
    {
        $roomCount = count($this->rooms);
        if ($roomCount > 2) {
            $opening = $this->isOpening ? 'open' : 'closed';
            echo "[{$room->identifier}] Warning: link '{$this->code}' ({$opening}) has more than 2 rooms ";
        }
        foreach ($this->rooms as $otherRoom) {
            if ($otherRoom->identifier != $room->identifier) {
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
            $roomIds[] = $room->identifier;
        }

        return $roomIds;
    }
}
