<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\File;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Tests\UnitTestCase;

// cspell:ignore äöüåøhello

/**
 * FileUploadSanitizeNameEvent tests.
 *
 * @group file
 * @coversDefaultClass \Drupal\Core\File\Event\FileUploadSanitizeNameEvent
 */
class FileUploadSanitizeNameEventTest extends UnitTestCase {

  /**
   * @covers ::setFilename
   * @covers ::getFilename
   */
  public function testSetFilename() {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame('foo.txt', $event->getFilename());
    $event->setFilename('foo.html');
    $this->assertSame('foo.html', $event->getFilename());
  }

  /**
   * @covers ::setFilename
   */
  public function testSetFilenameException() {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame('foo.txt', $event->getFilename());
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$filename must be a filename with no path information, "bar/foo.html" provided');
    $event->setFilename('bar/foo.html');
  }

  /**
   * @covers ::__construct
   * @covers ::setFilename
   */
  public function testConstructorException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$filename must be a filename with no path information, "bar/foo.txt" provided');
    new FileUploadSanitizeNameEvent('bar/foo.txt', '');
  }

  /**
   * @covers ::getAllowedExtensions
   */
  public function testAllowedExtensions() {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame([], $event->getAllowedExtensions());

    $event = new FileUploadSanitizeNameEvent('foo.txt', 'gif png');
    $this->assertSame(['gif', 'png'], $event->getAllowedExtensions());
  }

  /**
   * Test event construction.
   *
   * @dataProvider provideFilenames
   * @covers ::__construct
   * @covers ::getFilename
   *
   * @param string $filename
   *   The filename to test
   */
  public function testEventFilenameFunctions(string $filename) {
    $event = new FileUploadSanitizeNameEvent($filename, '');
    $this->assertSame($filename, $event->getFilename());
  }

  /**
   * Provides data for testEventFilenameFunctions().
   *
   * @return array
   *   Arrays with original file name.
   */
  public function provideFilenames() {
    return [
      'ASCII filename with extension' => [
        'example.txt',
      ],
      'ASCII filename with complex extension' => [
        'example.html.twig',
      ],
      'ASCII filename with lots of dots' => [
        'dotty.....txt',
      ],
      'Unicode filename with extension' => [
        'Ä Ö Ü Å Ø äöüåøhello.txt',
      ],
      'Unicode filename without extension' => [
        'Ä Ö Ü Å Ø äöüåøhello',
      ],
    ];
  }

  /**
   * @covers ::stopPropagation
   */
  public function testStopPropagation() {
    $this->expectException(\RuntimeException::class);
    $event = new FileUploadSanitizeNameEvent('test.txt', '');
    $event->stopPropagation();
  }

}
