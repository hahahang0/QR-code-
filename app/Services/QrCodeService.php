<?php

namespace App\Services;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    /** @return array{svg: string, png: string} */
    public function generate(string $payload, string $errorCorrection = 'Q', int $size = 1024): array
    {
        if (! in_array($errorCorrection, ['Q', 'H'], true)) {
            throw new \InvalidArgumentException('Use error correction Q or H.');
        }

        if (! mb_check_encoding($payload, 'UTF-8')) {
            throw new \InvalidArgumentException('QR data must be valid UTF-8.');
        }

        if (! in_array($size, [512, 1024, 2048], true)) {
            throw new \InvalidArgumentException('QR image size must be 512, 1024, or 2048 pixels.');
        }

        // ASCII is byte-for-byte identical in ISO-8859-1 and UTF-8. Omitting an
        // unnecessary UTF-8 ECI segment for these common JSON payloads improves
        // compatibility with readers that do not handle ECI consistently.
        $encoding = preg_match('/^[\x00-\x7F]*$/D', $payload) === 1 ? 'ISO-8859-1' : 'UTF-8';
        $correction = $errorCorrection === 'H' ? ErrorCorrectionLevel::High : ErrorCorrectionLevel::Quartile;

        $probe = new QrCode(
            data: $payload,
            encoding: new Encoding($encoding),
            errorCorrectionLevel: $correction,
            size: $size,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $moduleCount = (new MatrixFactory)->create($probe)->getBlockCount();
        $moduleSize = max(1, intdiv($size, $moduleCount));
        $quietZone = 4 * $moduleSize;

        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding($encoding),
            errorCorrectionLevel: $correction,
            size: $size,
            margin: $quietZone,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $svg = (new SvgWriter)->write($qrCode);
        $png = (new PngWriter)->write($qrCode);

        return [
            'svg' => $svg->getDataUri(),
            'png' => $png->getDataUri(),
        ];
    }
}
