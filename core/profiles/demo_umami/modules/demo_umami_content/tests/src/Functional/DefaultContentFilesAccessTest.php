<?php

namespace Drupal\Tests\demo_umami_content\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that files provided by demo_umami_content are not accessible.
 *
 * Note that this test only installs the testing profile because the Umami
 * profile is not required for the test.
 *
 * @group demo_umami_content
 */
class DefaultContentFilesAccessTest extends BrowserTestBase {

  /**
   * Tests that sample images, recipes and articles are not accessible.
   */
  public function testAccessDeniedToFiles() {
    $file_name = 'chocolate-brownie-umami.jpg';
    $file_path = '/' . drupal_get_path('module', 'demo_umami_content') . '/default_content/images/' . $file_name;
    $this->assertTrue(file_exists(DRUPAL_ROOT . $file_path));
    $this->drupalGet($file_path);
    $this->assertSession()->statusCodeEquals(403);

    $file_name = 'chocolate-brownie-umami.html';
    $file_path = '/' . drupal_get_path('module', 'demo_umami_content') . '/default_content/recipe_instructions/' . $file_name;
    $this->assertTrue(file_exists(DRUPAL_ROOT . $file_path));
    $this->drupalGet($file_path);
    $this->assertSession()->statusCodeEquals(403);

    $file_name = 'lets-hear-it-for-carrots.html';
    $file_path = '/' . drupal_get_path('module', 'demo_umami_content') . '/default_content/article_body/' . $file_name;
    $this->assertTrue(file_exists(DRUPAL_ROOT . $file_path));
    $this->drupalGet($file_path);
    $this->assertSession()->statusCodeEquals(403);

    $file_name = 'articles.csv';
    $file_path = '/' . drupal_get_path('module', 'demo_umami_content') . '/default_content/' . $file_name;
    $this->assertTrue(file_exists(DRUPAL_ROOT . $file_path));
    $this->drupalGet($file_path);
    $this->assertSession()->statusCodeEquals(403);
  }

}
