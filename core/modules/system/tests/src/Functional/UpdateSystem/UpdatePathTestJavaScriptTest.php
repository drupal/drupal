<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests the presence of JavaScript at update.php.
 *
 * @group Update
 */
class UpdatePathTestJavaScriptTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ensureUpdatesToRun();
  }

  /**
   * Tests JavaScript loading at update.php.
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
      if (!$script->getAttribute('src')) {
        continue;
      }
      // Source is a root-relative URL. Transform it to an absolute URL to allow
      // file_get_contents() to access the file.
      $src = preg_replace('#^' . $GLOBALS['base_path'] . '(.*)#i', $GLOBALS['base_url'] . '/' . '${1}', $script->getAttribute('src'));
      $file_content = file_get_contents($src);

      if (str_contains($file_content, 'window.drupalSettings =')) {
        $found = TRUE;
        break;
      }
    }

    $this->assertTrue($found, 'Ensure that the drupalSettingsLoader.js was included in the JS files');
  }

}
