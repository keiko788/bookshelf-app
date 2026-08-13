<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Expired = 'expired';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
