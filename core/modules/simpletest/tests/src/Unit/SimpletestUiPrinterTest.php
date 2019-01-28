<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Tests\Listeners\SimpletestUiPrinter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Tests\Listeners\SimpletestUiPrinter
 *
 * @group simpletest
 */
class SimpletestUiPrinterTest extends UnitTestCase {

  /**
   * Data provider for testWrite().
   *
   * @return string[]
   *   Array of data for testWrite().
   *   - Expected output from SimpletestUiPrinter->write().
   *   - Buffer to pass into SimpletestUiPrinter->write().
   */
  public function provideBuffer() {
    return [
      ['&amp;&quot;&#039;&lt;&gt;', '&"\'<>'],
      ['<a href="http:////www.example.com" target="_blank" title="http:////www.example.com">http:////www.example.com</a>', 'http:////www.example.com'],
      ['this is some text <a href="http://www.example.com/" target="_blank" title="http://www.example.com/">http://www.example.com/</a> with a link in it.', 'this is some text http://www.example.com/ with a link in it.'],
      ["HTML output was generated<br />\n", "HTML output was generated\n"],
    ];
  }

  /**
   * @covers ::write
   *
   * @dataProvider provideBuffer
   */
  public function testWrite($expected, $buffer) {
    $printer = new SimpletestUiPrinter();
    // Set up our expectation.
    $this->expectOutputString($expected);
    // Write the buffer.
    $printer->write($buffer);
  }

}
