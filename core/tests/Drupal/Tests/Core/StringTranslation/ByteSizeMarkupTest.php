<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\StringTranslation\ByteSizeMarkup
 * @group StringTranslation
 */
class ByteSizeMarkupTest extends UnitTestCase {

  /**
   * @covers ::create
   * @dataProvider providerTestCommonFormatSize
   */
  public function testCommonFormatSize($expected, $input): void {
    $size = ByteSizeMarkup::create($input, NULL, $this->getStringTranslationStub());
    $this->assertInstanceOf(TranslatableMarkup::class, $size);
    $this->assertEquals($expected, $size);
  }

  /**
   * Provides a list of byte size to test.
   */
  public static function providerTestCommonFormatSize() {
    $kb = Bytes::KILOBYTE;
    return [
      ['0 bytes', 0],
      // @todo https://www.drupal.org/node/3161118 Prevent display of fractional
      //   bytes for size less then 1KB.
      ['0.1 bytes', 0.1],
      ['0.6 bytes', 0.6],
      ['1 byte', 1],
      ['-1 bytes', -1],
      ['2 bytes', 2],
      ['-2 bytes', -2],
      ['1023 bytes', $kb - 1],
      ['1 KB', $kb],
      ['1 MB', pow($kb, 2)],
      ['1 GB', pow($kb, 3)],
      ['1 TB', pow($kb, 4)],
      ['1 PB', pow($kb, 5)],
      ['1 EB', pow($kb, 6)],
      ['1 ZB', pow($kb, 7)],
      ['1 YB', pow($kb, 8)],
      ['1024 YB', pow($kb, 9)],
      // Rounded to 1 MB - not 1000 or 1024 kilobytes
      ['1 MB', ($kb * $kb) - 1],
      ['-1 MB', -(($kb * $kb) - 1)],
      // Decimal Megabytes
      ['3.46 MB', 3623651],
      ['3.77 GB', 4053371676],
      // Decimal Petabytes
      ['59.72 PB', 67234178751368124],
      // Decimal Yottabytes
      ['194.67 YB', 235346823821125814962843827],
    ];
  }

  /**
   * @covers ::create
   */
  public function testTranslatableMarkupObject(): void {
    $result = ByteSizeMarkup::create(1, NULL, $this->getStringTranslationStub());
    $this->assertInstanceOf(PluralTranslatableMarkup::class, $result);
    $this->assertEquals("1 byte\03@count bytes", $result->getUntranslatedString());

    $result = ByteSizeMarkup::create(1048576, 'fr', $this->getStringTranslationStub());
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertEquals("@size MB", $result->getUntranslatedString());
    $this->assertEquals('fr', $result->getOption('langcode'));
  }

}
