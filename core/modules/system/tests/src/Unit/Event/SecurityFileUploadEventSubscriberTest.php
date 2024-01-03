<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\Event;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * SecurityFileUploadEventSubscriber tests.
 *
 * @group system
 * @coversDefaultClass \Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber
 */
class SecurityFileUploadEventSubscriberTest extends UnitTestCase {

  /**
   * Tests file name sanitization.
   *
   * @param string $filename
   *   The original filename.
   * @param string $allowed_extensions
   *   The allowed extensions.
   * @param string $expected_filename
   *   The expected filename if 'allow_insecure_uploads' is set to FALSE.
   * @param string|null $expected_filename_with_insecure_uploads
   *   The expected filename if 'allow_insecure_uploads' is set to TRUE.
   *
   * @dataProvider provideFilenames
   *
   * @covers ::sanitizeName
   */
  public function testSanitizeName(string $filename, string $allowed_extensions, string $expected_filename, string $expected_filename_with_insecure_uploads = NULL) {
    // Configure insecure uploads to be renamed.
    $config_factory = $this->getConfigFactoryStub([
      'system.file' => [
        'allow_insecure_uploads' => FALSE,
      ],
    ]);

    $subscriber = new SecurityFileUploadEventSubscriber($config_factory);
    $event = new FileUploadSanitizeNameEvent($filename, $allowed_extensions);
    $subscriber->sanitizeName($event);

    // Check the results of the configured sanitization.
    $this->assertSame($expected_filename, $event->getFilename());
    $this->assertSame($expected_filename !== $filename, $event->isSecurityRename());

    // Rerun the event allowing insecure uploads.
    $config_factory = $this->getConfigFactoryStub([
      'system.file' => [
        'allow_insecure_uploads' => TRUE,
      ],
    ]);

    $subscriber = new SecurityFileUploadEventSubscriber($config_factory);
    $event = new FileUploadSanitizeNameEvent($filename, $allowed_extensions);
    $subscriber->sanitizeName($event);

    // Check the results of the configured sanitization.
    $expected_filename_with_insecure_uploads = $expected_filename_with_insecure_uploads ?? $expected_filename;
    $this->assertSame($expected_filename_with_insecure_uploads, $event->getFilename());
    $this->assertSame($expected_filename_with_insecure_uploads !== $filename, $event->isSecurityRename());
  }

  /**
   * Provides data for testSanitizeName().
   *
   * @return array
   *   Arrays with original name, allowed extensions, expected name and
   *   (optional) expected name 'allow_insecure_uploads' is set to TRUE.
   */
  public function provideFilenames() {
    return [
      'All extensions allowed filename not munged' => ['foo.txt', '', 'foo.txt'],
      'All extensions allowed with .php file' => ['foo.php', '', 'foo.php_.txt', 'foo.php'],
      'All extensions allowed with .pHp file' => ['foo.pHp', '', 'foo.pHp_.txt', 'foo.pHp'],
      'All extensions allowed with .PHP file' => ['foo.PHP', '', 'foo.PHP_.txt', 'foo.PHP'],
      '.php extension allowed with .php file' => ['foo.php', 'php', 'foo.php', 'foo.php'],
      '.PhP extension allowed with .php file' => ['foo.php', 'PhP', 'foo.php', 'foo.php'],
      '.php, .txt extension allowed with .php file' => ['foo.php', 'php txt', 'foo.php_.txt', 'foo.php'],
      '.PhP, .tXt extension allowed with .php file' => ['foo.php', 'PhP tXt', 'foo.php_.txt', 'foo.php'],
      'no extension produces no errors' => ['foo', '', 'foo'],
      'filename is munged' => ['foo.phar.png.php.jpg', 'jpg png', 'foo.phar_.png_.php_.jpg'],
      'filename is munged regardless of case' => ['FOO.pHAR.PNG.PhP.jpg', 'jpg png', 'FOO.pHAR_.PNG_.PhP_.jpg'],
      'null bytes are removed' => ['foo' . chr(0) . '.txt' . chr(0), '', 'foo.txt'],
      'dot files are renamed' => ['.git', '', 'git'],
      'htaccess files are renamed even if allowed' => ['.htaccess', 'htaccess txt', '.htaccess_.txt', '.htaccess'],
      '.phtml extension allowed with .phtml file' => ['foo.phtml', 'phtml', 'foo.phtml'],
      '.phtml, .txt extension allowed with .phtml file' => ['foo.phtml', 'phtml txt', 'foo.phtml_.txt', 'foo.phtml'],
      'All extensions allowed with .phtml file' => ['foo.phtml', '', 'foo.phtml_.txt', 'foo.phtml'],
    ];
  }

  /**
   * Tests file name sanitization without file munging.
   *
   * @param string $filename
   *   The original filename.
   * @param string $allowed_extensions
   *   The allowed extensions.
   *
   * @dataProvider provideFilenamesNoMunge
   *
   * @covers ::sanitizeName
   */
  public function testSanitizeNameNoMunge(string $filename, string $allowed_extensions) {
    $config_factory = $this->getConfigFactoryStub([
      'system.file' => [
        'allow_insecure_uploads' => FALSE,
      ],
    ]);

    $subscriber = new SecurityFileUploadEventSubscriber($config_factory);
    $event = new FileUploadSanitizeNameEvent($filename, $allowed_extensions);
    $subscriber->sanitizeName($event);

    // Check the results of the configured sanitization.
    $this->assertSame($filename, $event->getFilename());
    $this->assertFalse($event->isSecurityRename());

    $config_factory = $this->getConfigFactoryStub([
      'system.file' => [
        'allow_insecure_uploads' => TRUE,
      ],
    ]);

    $event = new FileUploadSanitizeNameEvent($filename, $allowed_extensions);
    $subscriber = new SecurityFileUploadEventSubscriber($config_factory);
    $subscriber->sanitizeName($event);

    // Check the results of the configured sanitization.
    $this->assertSame($filename, $event->getFilename());
    $this->assertFalse($event->isSecurityRename());
  }

  /**
   * Provides data for testSanitizeNameNoMunge().
   *
   * @return array
   *   Arrays with original name and allowed extensions.
   */
  public function provideFilenamesNoMunge() {
    return [
      // The following filename would be rejected by 'FileExtension' constraint
      // and therefore remains unchanged.
      '.php is not munged when it would be rejected' => ['foo.php.php', 'jpg'],
      '.php is not munged when it would be rejected and filename contains null byte character' => ['foo.' . chr(0) . 'php.php', 'jpg'],
      'extension less files are not munged when they would be rejected' => ['foo', 'jpg'],
      'dot files are not munged when they would be rejected' => ['.htaccess', 'jpg png'],
    ];
  }

}
