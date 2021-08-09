<?php
require_once "vendor/autoload.php";

use Calcinai\PHPi\Exception\InvalidPinFunctionException;
use Calcinai\PHPi\Exception\UnsupportedBoardException;
use Calcinai\PHPi\Factory;
use Calcinai\PHPi\Pin\PinFunction;
use Pi2151\Devices\Mcp23s17;
use Pi2151\Devices\Si351a;
use Pi2151\Devices\Ym2151;
use Pi2151\VgmPlayer;

if ($argc < 2){
    die("php play.php filename.vgm\n");
}

try {
    $board = Factory::create();
} catch (UnsupportedBoardException $e) {
    die($e->getMessage());
}

// Init clock generator
$si351a = new Si351a();

// Init SPI0 for MCP23S17
try {
    $board->getPin(10)->setFunction(PinFunction::SPI0_MOSI);
    $board->getPin(9)->setFunction(PinFunction::SPI0_MISO);
    $board->getPin(11)->setFunction(PinFunction::SPI0_SCLK);
    $board->getPin(8)->setFunction(PinFunction::SPI0_CE0_N);
} catch (InvalidPinFunctionException $e) {
    die($e->getMessage());
}

// Init MCP23S17
try {
    $pinReset = $board->getPin(17)->setFunction(PinFunction::OUTPUT);
} catch (InvalidPinFunctionException $e) {
    die($e->getMessage());
}
$spi0 = $board->getSPI(0)->setClockSpeed(10 * 1e6);
$ym2151io = new Mcp23s17($spi0, 0, $pinReset);
$ym2151io->init();

// Init YM2151
$ym2151 = new Ym2151($ym2151io);
$ym2151->init();
$ym2151->reset();

// Init player & play
$player = new VgmPlayer($ym2151);
$fileName = $argv[1];
printf("Playing %s\n", $fileName);
$player->play($fileName);
printf("Finished.");
