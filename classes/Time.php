<?php
namespace Pi2151;

class Time
{
    public static function sleep_us(int $us): void
    {
        time_nanosleep(0, $us * 1000);
    }

    public static function sleep_ms(int $ms): void
    {
        time_nanosleep(0, $ms * 1000 * 1000);
    }
}
