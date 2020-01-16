<?php

declare(strict_types=1);

namespace Yogarine\Timelost;

class Link
{
    /**
     * @var string
     */
    public $code;

    /**
     * @var bool
     */
    public $isOpening;

    /**
     * @var Room[]
     */
    public $rooms = [];

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
