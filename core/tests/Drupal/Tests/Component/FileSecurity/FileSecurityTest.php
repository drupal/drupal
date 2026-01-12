<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\FileSecurity;

use Drupal\Component\FileSecurity\FileSecurity;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the file security component.
 */
#[CoversClass(FileSecurity::class)]
#[Group('FileSecurity')]
class FileSecurityTest extends TestCase {

  /**
   * Tests write htaccess private.
   */
  public function testWriteHtaccessPrivate(): void {
    vfsStream::setup('root');
    FileSecurity::writeHtaccess(vfsStream::url('root'));
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    $this->assertFileExists($htaccess_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($htaccess_file)), -4));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringContainsString("Require all denied", $htaccess_contents);
  }

  /**
   * Tests write htaccess public.
   */
  public function testWriteHtaccessPublic(): void {
    vfsStream::setup('root');
    $this->assertTrue(FileSecurity::writeHtaccess(vfsStream::url('root'), FALSE));
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    $this->assertFileExists($htaccess_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($htaccess_file)), -4));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringNotContainsString("Require all denied", $htaccess_contents);
  }

  /**
   * Tests write htaccess force overwrite.
   */
  public function testWriteHtaccessForceOverwrite(): void {
    vfsStream::setup('root');
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    file_put_contents($htaccess_file, "foo");
    $this->assertTrue(FileSecurity::writeHtaccess(vfsStream::url('root'), TRUE, TRUE));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringContainsString("Require all denied", $htaccess_contents);
    $this->assertStringNotContainsString("foo", $htaccess_contents);
  }

  /**
   * Tests write htaccess failure.
   */
  public function testWriteHtaccessFailure(): void {
    vfsStream::setup('root');
    $this->assertFalse(FileSecurity::writeHtaccess(vfsStream::url('root') . '/foo'));
  }

}
