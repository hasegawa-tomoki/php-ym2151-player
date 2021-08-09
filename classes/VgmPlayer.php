<?php
namespace Pi2151;

use Pi2151\Devices\Ym2151;

class VgmPlayer
{
    private $_ym2151;
    private $fp;
    private $_vgmpos = 0x40;
    private $_vgmend = false;
    private $_startTime;
    private $_durationUs = 0;
    private $_bufferOffset = 0;
    private $_buffer;
    private $_bufferSize = 16384;

    public function __construct(Ym2151 $ym2151)
    {
        $this->_ym2151 = $ym2151;
    }

    private function getSampleUs(int $samples): int
    {
        return (int)((1000.0 / (44100.0 / $samples)) * 1000);
    }

    private function pause(int $samples): void
    {
        $this->_durationUs = $this->getSampleUs($samples);
        $this->_startTime = $this->getAbsoluteTime();
    }

    private function open(string $filename): bool
    {
        $this->fp = fopen($filename, 'r');
        if ($this->fp === false){
            return false;
        }
        $this->loadBuffer(0);
        return true;
    }

    private function loadBuffer(int $offset)
    {
        fseek($this->fp, $offset);
        $this->_buffer = fread($this->fp, $this->_bufferSize);
        $this->_bufferOffset = $offset;
    }

    private function getByte(int $addr): int
    {
        if ($this->_bufferOffset <= $addr and $addr <= $this->_bufferOffset + $this->_bufferSize){
            return ord(substr($this->_buffer, $addr - $this->_bufferOffset, 1));
        }
        $this->loadBuffer($addr);
        return $this->getByte($addr);
    }

    private function getWord(int $addr): int
    {
        $b1 = $this->getByte($addr + 1);
        $b2 = $this->getByte($addr);
        return ($b1 << 8) + $b2;
    }

    private function getAbsoluteTime(): int
    {
        return intval(microtime(true) * 1000 * 1000);
    }

    private function loop(): void
    {
        $diff = $this->getAbsoluteTime() - $this->_startTime;
        if ($diff < $this->_durationUs){
            return;
        }

        $command = $this->getByte($this->_vgmpos);
        $this->_vgmpos++;

        switch ($command){
            case 0x54:
                $reg = $this->getByte($this->_vgmpos);
                $this->_vgmpos++;
                $data = $this->getByte($this->_vgmpos);
                $this->_vgmpos++;
                $this->_ym2151->write($reg, $data);

                $this->pause(1);
                break;
            case 0x61:
                $this->pause($this->getWord($this->_vgmpos));
                $this->_vgmpos += 2;
                break;
            case 0x62:
                $this->pause(735);
                break;
            case 0x63:
                $this->pause(882);
                break;
            case 0x66:
                $this->_vgmend = true;
                break;
        }
        if (0x70 <= $command && $command <= 0x7f){
            $wait = ($command & 0x0f) + 1;
            $this->pause($wait);
        }
    }

    public function play(string $fileName): void
    {
        if (! $this->open($fileName)){
            die("File open error.\n");
        }

        $this->_startTime = $this->getAbsoluteTime();
        while (! $this->_vgmend){
            $this->loop();
        }
    }
}
