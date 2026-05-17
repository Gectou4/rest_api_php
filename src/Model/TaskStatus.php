<?php

declare(strict_types=1);

namespace G4\Api\Model;

enum TaskStatus: int
{
    case Backlog    = 1;
    case Todo       = 2;
    case InProgress = 3;
    case Done       = 4;
    case Closed     = 5;
}
