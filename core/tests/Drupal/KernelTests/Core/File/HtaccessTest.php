<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests .htaccess file saving.
 *
 * @coversDefaultClass \Drupal\Core\File\HtaccessWriter
 * @group File
 */
class HtaccessTest extends KernelTestBase {

  /**
   * The public directory.
   *
   * @var string
   */
  protected $public;

  /**
   * The Htaccess class under test.
   *
   * @var \Drupal\Core\File\HtaccessWriterInterface
   */
  protected $htaccessWriter;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->public = Settings::get('file_public_path') . '/test/public';
    $this->htaccessWriter = $this->container->get('file.htaccess_writer');
  }

  /**
   * @covers ::write
   */
  public function testHtaccessSave() {
    // Prepare test directories.
    $private = Settings::get('file_public_path') . '/test/private';
    $stream = 'public://test/stream';

    // Create public .htaccess file.
    mkdir($this->public, 0777, TRUE);
    $this->assertTrue($this->htaccessWriter->write($this->public, FALSE));
    $content = file_get_contents($this->public . '/.htaccess');
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006", $content);
    $this->assertStringNotContainsString("Require all denied", $content);
    $this->assertStringNotContainsString("Deny from all", $content);
    $this->assertStringContainsString("Options -Indexes -ExecCGI -Includes -MultiViews", $content);
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003", $content);
    $this->assertFilePermissions($this->public . '/.htaccess', 0444);

    $this->assertTrue($this->htaccessWriter->write($this->public, FALSE));

    // Create private .htaccess file.
    mkdir($private, 0777, TRUE);
    $this->assertTrue($this->htaccessWriter->write($private));
    $content = file_get_contents($private . '/.htaccess');
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006", $content);
    $this->assertStringContainsString("Require all denied", $content);
    $this->assertStringContainsString("Deny from all", $content);
    $this->assertStringContainsString("Options -Indexes -ExecCGI -Includes -MultiViews", $content);
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003", $content);
    $this->assertFilePermissions($private . '/.htaccess', 0444);

    $this->assertTrue($this->htaccessWriter->write($private));

    // Create an .htaccess file using a stream URI.
    mkdir($stream, 0777, TRUE);
    $this->assertTrue($this->htaccessWriter->write($stream));
    $content = file_get_contents($stream . '/.htaccess');
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006", $content);
    $this->assertStringContainsString("Require all denied", $content);
    $this->assertStringContainsString("Deny from all", $content);
    $this->assertStringContainsString("Options -Indexes -ExecCGI -Includes -MultiViews", $content);
    $this->assertStringContainsString("SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003", $content);
    $this->assertFilePermissions($stream . '/.htaccess', 0444);

    $this->assertTrue($this->htaccessWriter->write($stream));
  }

  /**
   * Asserts expected file permissions for a given file.
   *
   * @param string $uri
   *   The URI of the file to check.
   * @param int $expected
   *   The expected file permissions; e.g., 0444.
   */
  protected function assertFilePermissions($uri, $expected) {
    $actual = fileperms($uri) & 0777;
    $this->assertSame($actual, $expected, new FormattableMarkup('@uri file permissions @actual are identical to @expected.', [
      '@uri' => $uri,
      '@actual' => 0 . decoct($actual),
      '@expected' => 0 . decoct($expected),
    ]));
  }

}
