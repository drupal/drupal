<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\File;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

// cspell:ignore äöüåøhello
/**
 * FileUploadSanitizeNameEvent tests.
 */
#[CoversClass(FileUploadSanitizeNameEvent::class)]
#[Group('file')]
class FileUploadSanitizeNameEventTest extends UnitTestCase {

  /**
   * Tests set filename.
   *
   * @legacy-covers ::setFilename
   * @legacy-covers ::getFilename
   */
  public function testSetFilename(): void {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame('foo.txt', $event->getFilename());
    $event->setFilename('foo.html');
    $this->assertSame('foo.html', $event->getFilename());
  }

  /**
   * Tests set filename exception.
   *
   * @legacy-covers ::setFilename
   */
  public function testSetFilenameException(): void {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame('foo.txt', $event->getFilename());
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$filename must be a filename with no path information, "bar/foo.html" provided');
    $event->setFilename('bar/foo.html');
  }

  /**
   * Tests constructor exception.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::setFilename
   */
  public function testConstructorException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('$filename must be a filename with no path information, "bar/foo.txt" provided');
    new FileUploadSanitizeNameEvent('bar/foo.txt', '');
  }

  /**
   * Tests allowed extensions.
   *
   * @legacy-covers ::getAllowedExtensions
   */
  public function testAllowedExtensions(): void {
    $event = new FileUploadSanitizeNameEvent('foo.txt', '');
    $this->assertSame([], $event->getAllowedExtensions());

    $event = new FileUploadSanitizeNameEvent('foo.txt', 'gif png');
    $this->assertSame(['gif', 'png'], $event->getAllowedExtensions());
  }

  /**
   * Test event construction.
   *
   * @param string $filename
   *   The filename to test.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getFilename
   */
  #[DataProvider('provideFilenames')]
  public function testEventFilenameFunctions(string $filename): void {
    $event = new FileUploadSanitizeNameEvent($filename, '');
    $this->assertSame($filename, $event->getFilename());
  }

  /**
   * Provides data for testEventFilenameFunctions().
   *
   * @return array
   *   Arrays with original file name.
   */
  public static function provideFilenames(): array {
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
   * Tests stop propagation.
   *
   * @legacy-covers ::stopPropagation
   */
  public function testStopPropagation(): void {
    $this->expectException(\RuntimeException::class);
    $event = new FileUploadSanitizeNameEvent('test.txt', '');
    $event->stopPropagation();
  }

}
