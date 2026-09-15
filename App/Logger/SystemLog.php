<?php
declare(strict_types=1);
namespace App\Logger;

class SystemLog extends AbstractLog
{
    public static $infoLogType ='system_error_log';
}