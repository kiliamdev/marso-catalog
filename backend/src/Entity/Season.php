<?php declare(strict_types=1);

namespace App\Entity;

enum Season: string
{
    case SUMMER = 'summer';
    case WINTER = 'winter';
    case ALL_SEASON = 'all_season';
}
