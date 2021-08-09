<?php
namespace Pi2151\Devices;

use Calcinai\PHPi\Exception\InvalidPinFunctionException;
use Calcinai\PHPi\Peripheral\SPI;
use Calcinai\PHPi\Pin;
use Pi2151\Time;

class Mcp23s17
{
    private $_spi;
    private $_deviceId;
    private $_pinReset;

    private $_gpioA = 0;
    private $_gpioB = 0;
    private $_ioDirA = 0;
    private $_ioDirB = 0;
    private $_gppuA = 0;
    private $_gppuB = 0;

    public const PULL_UP_ENABLED = 0;
    public const PULL_UP_DISABLED = 1;
    public const DIR_INPUT = 0;
    public const DIR_OUTPUT = 1;
    public const LEVEL_LOW = 0;
    public const LEVEL_HIGH = 1;

    // Registers
    public const MCP23S17_GPIO_A = 0x12;
    public const MCP23S17_GPIO_B = 0x13;
    private const MCP23S17_IO_DIR_A = 0x00;
    private const MCP23S17_IO_DIR_B = 0x01;
    private const MCP23S17_IOCON = 0x0A;
    private const MCP23S17_GPPU_A = 0x0C;
    private const MCP23S17_GPPU_B = 0x0D;

    // IOCON:  bit7   bit6   bit5   bit4   bit3   bit2   bit1   bit0
    //         BANK   MIRROR SEQOP  DISSLW HAEN   ODR    INTPOL -
    private const IOCON_INIT = 0b00101000;

    // Control: bit7 bit6  bit5  bit4  bit3  bit2  bit1  bit0
    //          0    1     0     0     A2    A1    A0    R/W
    private const MCP23S17_CMD_WRITE = 0x40;
    private const MCP23S17_CMD_READ = 0x41;

    public function __construct(SPI $spi, int $deviceId, Pin $_pinReset)
    {
        $this->_spi = $spi;
        $this->_deviceId = $deviceId;
        $this->_pinReset = $_pinReset;
    }

    public function init(): void
    {
        $this->reset();
        Time::sleep_ms(2);
        $this->writeRegister(self::MCP23S17_IOCON, self::IOCON_INIT);

        for ($idx = 0; $idx < 16; $idx++){
            $this->setDirection($idx, self::DIR_INPUT);
        }
        for ($idx = 0; $idx < 16; $idx++){
            $this->setPullUpMode($idx, self::PULL_UP_ENABLED);
        }
        $this->_spi->chipSelect(0);
    }

    private function reset(): void
    {
        try {
            $this->_pinReset->low();
            Time::sleep_us(2);
            $this->_pinReset->high();
            Time::sleep_us(2);
        } catch (InvalidPinFunctionException $e) {
            die($e->getMessage());
        }
    }

    public function writeRegister(int $reg, int $value): void
    {
        $command = self::MCP23S17_CMD_WRITE | ($this->_deviceId <<1);
        $this->_spi->transfer(pack('C*', $command, $reg, $value));
    }

    public function writeRegisterSeq(int $reg, array $values): void
    {
        $command = self::MCP23S17_CMD_WRITE | ($this->_deviceId <<1);
        $this->_spi->transfer(pack('C*', $command, $reg, ...$values));
    }

    public function readRegister(int $reg): string
    {
        $command = self::MCP23S17_CMD_READ | ($this->_deviceId <<1);
        return $this->_spi->transfer(pack('C*', [$command, $reg, 0]));
    }

    public function setDirection(int $pin, int $direction): void
    {
        if ($pin >= 16){
            return;
        }
        if ($direction != self::DIR_INPUT && $direction != self::DIR_OUTPUT){
            return;
        }

        if ($pin < 8){
            $reg = self::MCP23S17_IO_DIR_A;
            $data = $this->_ioDirA;
            $noShifts = $pin;
        } else {
            $reg = self::MCP23S17_IO_DIR_B;
            $data = $this->_ioDirB;
            $noShifts = $pin & 0x07;
        }

        if ($direction == self::DIR_INPUT){
            $data |= (1 << $noShifts);
        } else {
            $data &= (~(1 << $noShifts));
        }

        $this->writeRegister($reg, $data);

        if ($pin < 8){
            $this->_ioDirA = $data;
        } else {
            $this->_ioDirB = $data;
        }
    }

    public function setPullUpMode(int $pin, int $mode): void
    {
        if ($pin >= 16){
            return;
        }
        if ($mode != self::PULL_UP_ENABLED && $mode != self::PULL_UP_DISABLED){
            return;
        }

        if ($pin < 8){
            $reg = self::MCP23S17_GPPU_A;
            $data = $this->_gppuA;
            $noShifts = $pin;
        } else {
            $reg = self::MCP23S17_GPPU_B;
            $data = $this->_gppuB;
            $noShifts = $pin & 0x07;
        }

        if ($mode == self::PULL_UP_ENABLED){
            $data |= (1 << $noShifts);
        } else {
            $data &= (~(1 << $noShifts));
        }

        $this->writeRegister($reg, $data);

        if ($pin < 8){
            $this->_gppuA = $data;
        } else {
            $this->_gppuB = $data;
        }
    }

    /** @noinspection PhpUnused */
    public function digitalRead(int $pin): string
    {
        if ($pin >= 16){
            return 0;
        }

        if ($pin < 8){
            $this->_gpioA = $this->readRegister(self::MCP23S17_GPIO_A);
            if (($this->_gpioA & (1 << $pin)) != 0){
                return self::LEVEL_HIGH;
            } else {
                return self::LEVEL_LOW;
            }
        } else {
            $this->_gpioB = $this->readRegister(self::MCP23S17_GPIO_B);
            $pin &= 0x07;
            if (($this->_gpioB & (1 << $pin)) != 0){
                return self::LEVEL_HIGH;
            } else {
                return self::LEVEL_LOW;
            }
        }
    }

    public function digitalWrite(int $pin, int $level, bool $internal = false): void
    {
        if ($pin >= 16){
            return;
        }
        if ($level != self::LEVEL_HIGH && $level != self::LEVEL_LOW){
            return;
        }

        if ($pin < 8){
            $reg = self::MCP23S17_GPIO_A;
            $data = $this->_gpioA;
            $noShifts = $pin;
        } else {
            $reg = self::MCP23S17_GPIO_B;
            $data = $this->_gpioB;
            $noShifts = $pin & 0x07;
        }

        if ($level == self::LEVEL_HIGH){
            $data |= (1 << $noShifts);
        } else {
            $data &= (~(1 << $noShifts));
        }

        if (! $internal){
            $this->writeRegister($reg, $data);
        }

        if ($pin < 8){
            $this->_gpioA = $data;
        } else {
            $this->_gpioB = $data;
        }
    }

    public function flushGpio(): void
    {
        $this->writeRegisterSeq(self::MCP23S17_GPIO_A, [$this->_gpioA, $this->_gpioB]);
    }

    public function writeGpio(string $data): void
    {
        $this->_gpioA = ($data & 0xff);
        $this->_gpioB = ($data >> 8);
        $this->writeRegisterSeq(self::MCP23S17_GPIO_A, [$this->_gpioA, $this->_gpioB]);
    }

    public function writeGpio8(int $gpio, string $data, bool $internal = false): void
    {
        if ($gpio == self::MCP23S17_GPIO_A){
            $this->_gpioA = $data;
        } else {
            $this->_gpioB = $data;
        }
        if (! $internal){
            $this->writeRegister($gpio, $data);
        }
    }

    /** @noinspection PhpUnused */
    public function readGpio8(int $gpio): string
    {
        if ($gpio == self::MCP23S17_GPIO_A){
            $data = $this->readRegister(self::MCP23S17_GPIO_A);
            $this->_gpioA = $data;
        } else {
            $data = $this->readRegister(self::MCP23S17_GPIO_B);
            $this->_gpioB = $data;
        }
        return $data;
    }
}
