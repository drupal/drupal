<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePathTestJavaScriptTest.php.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests the presence of JavaScript at update.php.
 *
 * @group Update
 */
class UpdatePathTestJavaScriptTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Test JavaScript loading at update.php.
   *
   * @see ::doPreUpdateTests
   */
  public function testJavaScriptLoading() {
    $this->runUpdates();
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    // Ensure that at least one JS script has drupalSettings in there.
    $scripts = $this->xpath('//script');
    $found = FALSE;
    foreach ($scripts as $script) {
      if (!isset($script['src'])) {
        continue;
      }
      $src = (string) $script['src'];
      $file_content = file_get_contents($src);

      if (strpos($file_content, 'window.drupalSettings =') !== FALSE) {
        $found = TRUE;
        break;
      }
    }

    $this->assertTrue($found, 'Ensure that the drupalSettingsLoader.js was included in the JS files');
  }

}
