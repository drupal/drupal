<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests multiple file upload.
 *
 * @group file
 */
class MultipleFileUploadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($admin);
  }

  /**
   * Tests multiple file field with all file extensions.
   */
  public function testMultipleFileFieldWithAllFileExtensions() {
    $theme = 'test_theme_settings';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->drupalGet("admin/appearance/settings/$theme");

    $edit = [];
    // Create few files with non-typical extensions.
    foreach (['file1.wtf', 'file2.wtf'] as $i => $file) {
      $file_path = $this->root . "/sites/default/files/simpletest/$file";
      file_put_contents($file_path, 'File with non-default extension.', FILE_APPEND | LOCK_EX);
      $edit["files[multi_file][$i]"] = $file_path;
    }

    // @todo: Replace after https://www.drupal.org/project/drupal/issues/2917885
    $this->drupalGet("admin/appearance/settings/$theme");
    $submit_xpath = $this->assertSession()->buttonExists('Save configuration')->getXpath();
    $client = $this->getSession()->getDriver()->getClient();
    $form = $client->getCrawler()->filterXPath($submit_xpath)->form();
    $client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $edit);

    $page = $this->getSession()->getPage();
    $this->assertNotContains('Only files with the following extensions are allowed', $page->getContent());
    $this->assertContains('The configuration options have been saved.', $page->getContent());
    $this->assertContains('file1.wtf', $page->getContent());
    $this->assertContains('file2.wtf', $page->getContent());
  }

}
