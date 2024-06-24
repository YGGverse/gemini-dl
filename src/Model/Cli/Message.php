<?php

declare(strict_types=1);

namespace Yggverse\GeminiDL\Model\Cli;

use \Codedungeon\PHPCliColors\Color;

class Message
{
    public static function red(
        string $message
    ): string
    {
        return self::plain(
            $message,
            Color::RED
        );
    }

    public static function magenta(
        string $message
    ): string
    {
        return self::plain(
            $message,
            Color::MAGENTA
        );
    }

    public static function blue(
        string $message,
        bool $bold = false
    ): string
    {
        return self::plain(
            $message,
            $bold ? Color::LIGHT_BLUE
                  : Color::BLUE
        );
    }

    public static function green(
        string $message
    ): string
    {
        return self::plain(
            $message,
            Color::GREEN
        );
    }

    public static function plain(
        string $message,
        string $style = null
    ): string
    {
        return Color::RESET . $style . $message . PHP_EOL;
    }
}