<?php
namespace Pi2151\Devices;

use Pi2151\Time;

class Ym2151
{
    private $_io;

    private const YM_PIN_RD = 0;
    private const YM_PIN_WR = 1;
    private const YM_PIN_A0 = 2;
    private const YM_PIN_IC = 3;

    private const LEVEL_LOW = 0;
    private const LEVEL_HIGH = 1;

    private const GPIO_BUS = Mcp23s17::MCP23S17_GPIO_B;

    public function __construct(Mcp23s17 $io)
    {
        $this->_io = $io;
    }

    public function init(): void
    {
        for ($x = 0; $x < 16; $x++){
            $this->_io->setDirection($x, Mcp23s17::DIR_OUTPUT);
        }
        $this->_io->writeGpio(0);
        Time::sleep_us(500);
    }

    public function reset():void
    {
        $this->_io->digitalWrite(self::YM_PIN_IC, self::LEVEL_HIGH);
        $this->_io->digitalWrite(self::YM_PIN_A0, self::LEVEL_HIGH);
        $this->_io->digitalWrite(self::YM_PIN_WR, self::LEVEL_HIGH);
        $this->_io->digitalWrite(self::YM_PIN_RD, self::LEVEL_HIGH);

        $this->_io->digitalWrite(self::YM_PIN_IC, self::LEVEL_LOW);
        Time::sleep_ms(200);
        $this->_io->digitalWrite(self::YM_PIN_IC, self::LEVEL_HIGH);
        Time::sleep_ms(200);

        printf("YM2151 reset.\n");
    }

    public function write(int $addr, string $data): void
    {
        //$now = microtime(true);
        // Output addr
        $this->_io->digitalWrite(self::YM_PIN_A0, self::LEVEL_LOW, true);
        $this->_io->writeGpio8(self::GPIO_BUS, $addr, true);
        $this->_io->flushGpio();
        // Write address
        $this->_io->digitalWrite(self::YM_PIN_WR, self::LEVEL_LOW);
        $this->_io->digitalWrite(self::YM_PIN_WR, self::LEVEL_HIGH, true);

        // Output data
        $this->_io->digitalWrite(self::YM_PIN_A0, self::LEVEL_HIGH, true);
        $this->_io->writeGpio8(self::GPIO_BUS, $data, true);
        $this->_io->flushGpio();
        // Write address
        $this->_io->digitalWrite(self::YM_PIN_WR, self::LEVEL_LOW);
        $this->_io->digitalWrite(self::YM_PIN_WR, self::LEVEL_HIGH, true);
        //printf("Write in %f micro sec.\n", (microtime(true) - $now) * 1000 * 1000);
    }
}
