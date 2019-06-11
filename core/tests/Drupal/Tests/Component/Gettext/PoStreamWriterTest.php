<?php

namespace Drupal\Tests\Component\Gettext;

use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoStreamWriter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Gettext\PoStreamWriter
 * @group Gettext
 */
class PoStreamWriterTest extends TestCase {

  /**
   * The PO writer object under test.
   *
   * @var \Drupal\Component\Gettext\PoStreamWriter
   */
  protected $poWriter;

  /**
   * The mock po file.
   *
   * @var \org\bovigo\vfs\vfsStreamFile
   */
  protected $poFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->poWriter = new PoStreamWriter();

    $root = vfsStream::setup();
    $this->poFile = new vfsStreamFile('powriter.po');
    $root->addChild($this->poFile);
  }

  /**
   * @covers ::getURI
   */
  public function testGetUriException() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(\Exception::class, 'No URI set.');
    }
    else {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('No URI set.');
    }

    $this->poWriter->getURI();
  }

  /**
   * @covers ::writeItem
   * @dataProvider providerWriteData
   */
  public function testWriteItem($poContent, $expected, $long) {
    if ($long) {
      if (method_exists($this, 'expectException')) {
        $this->expectException(\Exception::class, 'Unable to write data:');
      }
      else {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to write data:');
      }
    }

    // Limit the file system quota to make the write fail on long strings.
    vfsStream::setQuota(10);

    $this->poWriter->setURI($this->poFile->url());
    $this->poWriter->open();

    $poItem = $this->prophesize(PoItem::class);
    $poItem->__toString()->willReturn($poContent);

    $this->poWriter->writeItem($poItem->reveal());
    $this->poWriter->close();
    $this->assertEquals(file_get_contents($this->poFile->url()), $expected);
  }

  /**
   * @return array
   *   - Content to write.
   *   - Written content.
   *   - Content longer than 10 bytes.
   */
  public function providerWriteData() {
    return [
      ['', '', FALSE],
      ["\r\n", "\r\n", FALSE],
      ['write this if you can', 'write this', TRUE],
      ['éáíó>&', 'éáíó>&', FALSE],
      ['éáíó>&<', 'éáíó>&', TRUE],
      ['中文 890', '中文 890', FALSE],
      ['中文 89012', '中文 890', TRUE],
    ];
  }

  /**
   * @covers ::close
   */
  public function testCloseException() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(\Exception::class, 'Cannot close stream that is not open.');
    }
    else {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('Cannot close stream that is not open.');
    }

    $this->poWriter->close();
  }

}
