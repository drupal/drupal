<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests multiple file upload.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class MultipleFileUploadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($admin);
  }

  /**
   * Tests multiple file field with all file extensions.
   *
   * When theme-settings.php deprecation is complete then convert
   * test_theme_settings theme-settings.php then remove the IgnoreDeprecations
   * attribute.
   */
  #[IgnoreDeprecations]
  public function testMultipleFileFieldWithAllFileExtensions(): void {
    $this->expectUserDeprecationMessage('Using a theme-settings.php file in the test_theme_settings theme is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Convert the hooks to use attributes or move to the .theme file. See https://www.drupal.org/node/3587273');
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

    // @todo Replace after https://www.drupal.org/project/drupal/issues/2917885
    $this->drupalGet("admin/appearance/settings/$theme");
    $submit_xpath = $this->assertSession()->buttonExists('Save configuration')->getXpath();
    $client = $this->getSession()->getDriver()->getClient();
    $form = $client->getCrawler()->filterXPath($submit_xpath)->form();
    $client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $edit);

    $page = $this->getSession()->getPage();
    $this->assertStringNotContainsString('Only files with the following extensions are allowed', $page->getContent());
    $this->assertStringContainsString('The configuration options have been saved.', $page->getContent());
    $this->assertStringContainsString('file1.wtf', $page->getContent());
    $this->assertStringContainsString('file2.wtf', $page->getContent());
  }

}
