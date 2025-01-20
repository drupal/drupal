<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\File;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the log message added by the HtaccessWriter service.
 *
 * @group File
 */
class FileSaveHtaccessLoggingTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the HtaccessWriter service write functionality.
   */
  public function testHtaccessSave(): void {
    // Prepare test directories.
    $private = $this->publicFilesDirectory . '/test/private';

    // Verify that HtaccessWriter service returns FALSE if .htaccess cannot be
    // written and writes a correctly formatted message to the error log.
    // Set $private to TRUE so all possible .htaccess lines are written.
    /** @var \Drupal\Core\File\HtaccessWriterInterface $htaccess */
    $htaccess = \Drupal::service('file.htaccess_writer');
    $this->assertFalse($htaccess->write($private, TRUE));
    $this->drupalLogin($this->drupalCreateUser(['access site reports']));

    $this->drupalGet('admin/reports/dblog');
    $this->clickLink("Security warning: Couldn't write .htaccess file.");

    $lines = FileSecurity::htaccessLines(TRUE);
    foreach (array_filter(explode("\n", $lines)) as $line) {
      $this->assertSession()->assertEscaped($line);
    }
  }

}
