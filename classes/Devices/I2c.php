<?php
namespace Pi2151\Devices;

/**
 * Class I2c
 *   SDA = GPIO2
 *   SCL = GPIO3
 *
 * @package Pi2151
 */
class I2c
{
    public static function write(int $deviceAddress, int $address, int $data): void
    {
        $cmd = sprintf('i2cset -y 1 0x%02x 0x%02x 0x%02x', $deviceAddress, $address, $data);
        exec($cmd);
    }

    public static function read(int $deviceAddress, int $address): int
    {
        $cmd = sprintf('i2cget -y 1 0x%02x 0x%02x', $deviceAddress, $address);
        exec($cmd, $output);
        return hexdec($output[0]);
    }
}
