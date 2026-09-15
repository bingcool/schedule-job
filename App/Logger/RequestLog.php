<?php
declare(strict_types=1);
namespace App\Logger;

class RequestLog extends AbstractLog
{
    public static $infoLogType = 'request_log';
}