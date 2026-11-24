<?php

namespace Kyledoesdev\Essentials\Backups\Enums;

enum SizeUnit: string
{
    case Bytes = 'B';
    case Kilobytes = 'KB';
    case Megabytes = 'MB';
    case Gigabytes = 'GB';
    case Terabytes = 'TB';
}
