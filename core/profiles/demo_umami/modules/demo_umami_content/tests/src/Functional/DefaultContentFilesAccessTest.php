<?php

namespace Drupal\Tests\demo_umami_content\Functional;

use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that files provided by demo_umami_content are not accessible.
 *
 * @group demo_umami_content
 */
class DefaultContentFilesAccessTest extends BrowserTestBase {

  /**
   * Tests that sample images, recipes and articles are not accessible.
   */
  public function testAccessDeniedToFiles() {
    // The demo_umami profile should not be used because we want to ensure that
    // if you install another profile these files are not available.
    $this->assertNotSame('demo_umami', Settings::get('install_profile'));

    $files_to_test = [
      'images/chocolate-brownie-umami.jpg',
      'recipe_instructions/chocolate-brownie-umami.html',
      'article_body/lets-hear-it-for-carrots.html',
      'articles.csv',
    ];
    foreach ($files_to_test as $file) {
      // Hard code the path since the demo_umami profile is not installed.
      $content_path = "core/profiles/demo_umami/modules/demo_umami_content/default_content/$file";
      $this->assertFileExists($this->root . '/' . $content_path);
      $this->drupalGet($content_path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

}
