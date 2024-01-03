<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\FileSecurity;

use Drupal\Component\FileSecurity\FileSecurity;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * Tests the file security component.
 *
 * @coversDefaultClass \Drupal\Component\FileSecurity\FileSecurity
 * @group FileSecurity
 */
class FileSecurityTest extends TestCase {

  /**
   * @covers ::writeHtaccess
   */
  public function testWriteHtaccessPrivate() {
    vfsStream::setup('root');
    FileSecurity::writeHtaccess(vfsStream::url('root'));
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    $this->assertFileExists($htaccess_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($htaccess_file)), -4));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringContainsString("Require all denied", $htaccess_contents);
  }

  /**
   * @covers ::writeHtaccess
   */
  public function testWriteHtaccessPublic() {
    vfsStream::setup('root');
    $this->assertTrue(FileSecurity::writeHtaccess(vfsStream::url('root'), FALSE));
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    $this->assertFileExists($htaccess_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($htaccess_file)), -4));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringNotContainsString("Require all denied", $htaccess_contents);
  }

  /**
   * @covers ::writeHtaccess
   */
  public function testWriteHtaccessForceOverwrite() {
    vfsStream::setup('root');
    $htaccess_file = vfsStream::url('root') . '/.htaccess';
    file_put_contents($htaccess_file, "foo");
    $this->assertTrue(FileSecurity::writeHtaccess(vfsStream::url('root'), TRUE, TRUE));
    $htaccess_contents = file_get_contents($htaccess_file);
    $this->assertStringContainsString("Require all denied", $htaccess_contents);
    $this->assertStringNotContainsString("foo", $htaccess_contents);
  }

  /**
   * @covers ::writeHtaccess
   */
  public function testWriteHtaccessFailure() {
    vfsStream::setup('root');
    $this->assertFalse(FileSecurity::writeHtaccess(vfsStream::url('root') . '/foo'));
  }

  /**
   * @covers ::writeWebConfig
   */
  public function testWriteWebConfig() {
    vfsStream::setup('root');
    $this->assertTrue(FileSecurity::writeWebConfig(vfsStream::url('root')));
    $web_config_file = vfsStream::url('root') . '/web.config';
    $this->assertFileExists($web_config_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($web_config_file)), -4));
  }

  /**
   * @covers ::writeWebConfig
   */
  public function testWriteWebConfigForceOverwrite() {
    vfsStream::setup('root');
    $web_config_file = vfsStream::url('root') . '/web.config';
    file_put_contents($web_config_file, "foo");
    $this->assertTrue(FileSecurity::writeWebConfig(vfsStream::url('root'), TRUE));
    $this->assertFileExists($web_config_file);
    $this->assertEquals('0444', substr(sprintf('%o', fileperms($web_config_file)), -4));
    $this->assertStringNotContainsString("foo", $web_config_file);
  }

  /**
   * @covers ::writeWebConfig
   */
  public function testWriteWebConfigFailure() {
    vfsStream::setup('root');
    $this->assertFalse(FileSecurity::writeWebConfig(vfsStream::url('root') . '/foo'));
  }

}
