<?php

namespace Test\ChromiumPdf;

use Beganovich\ChromiumPdf\ChromiumPdf;
use Beganovich\ChromiumPdf\Exceptions\MissingContent;
use PHPUnit\Framework\TestCase;

class ChromiumPdfTest extends TestCase
{
    static $chromiumPath = '/usr/bin/google-chrome';

    public function testGeneratingPdfWorks()
    {
        $chromiumPdf = new ChromiumPdf();
        $html = file_get_contents(dirname(__DIR__, 1) . '/tests/template.html');

        $pdf = $chromiumPdf
            ->setChromiumPath(self::$chromiumPath)
            ->setHtml($html)
            ->generate();

        $this->assertNotNull($pdf);
    }

    public function testMissingContentShouldBeThrown()
    {
        $this->expectException(MissingContent::class);
        $this->expectExceptionMessage('No content provided. Make sure you call setHtml() or setUrl() before generate().');

        $chromiumPdf = new ChromiumPdf();

        $chromiumPdf
            ->setChromiumPath(self::$chromiumPath)
            ->generate();
    }
}
