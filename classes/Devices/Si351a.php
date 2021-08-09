<?php
namespace Pi2151\Devices;

Class Si351a
{
    private const ADDRESS = 0x60;

    public function __construct()
    {
        // Disable Output
        $this->write(3, 0xff);
        // CLOCK0 Power down
        $this->write(16, 0x80);

        $this->setPlla();
        $this->setMs0();

        // Reset PLLA and PLLB
        $this->write(177, 0xA0);
        // CLOCK0 Power up 8mA
        $this->write(16, 0x4F);
        // Enable CLOCK0
        $this->write(3, 0xFE);
    }

    private function write(int $address, int $data): void
    {
        I2c::write(self::ADDRESS, $address, $data);
    }

    private function setPlla(): void
    {
        // MSNA_P3[15:8]
        $this->write(26, 0);
        // MSNA_P3[7:0]
        $this->write(27, 1);
        // P1 = 00|00000110|00000000 = 1,536
        // MSNA_P1[17:16]
        $this->write(28, 0);
        // MSNA_P1[15:8]
        $this->write(29, 0b00000110);
        // MSNA_P1[7:0]
        $this->write(30, 0);
        // MSNA_P3[19:16]MSNA_P2[19:16]
        $this->write(31, 0);
        // MSNA_P2[15:8]
        $this->write(32, 0);
        // MSNA_P2[7:0]
        $this->write(33, 0);
    }

    private function setMs0(): void
    {
        // a = 100, b = 0, c = 1
        // MS0_P3[15:8]
        $this->write(42, 0);
        // MS0_P3[7:0]
        $this->write(43, 1);
        // P1 = 00|00110000|00000000 = 12,288
        // MS0_P1[17:16]
        $this->write(44, 0);
        // MS0_P1[15:8]
        $this->write(45, 0b00110000);
        // MS0_P1[7:0]
        $this->write(46, 0);
        // MS0_P3[19:16]MS0_P2[19:16]
        $this->write(47, 0);
        // MS0_P2[15:8]
        $this->write(48, 0);
        // MS0_P2[7:0]
        $this->write(49, 0);
    }
}
