<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests browser language detection.
 *
 * @group language
 */
class LanguageBrowserDetectionTest extends BrowserTestBase {

  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests mappings between browser language codes and Drupal language codes.
   */
  public function testUIBrowserLanguageMappings() {
    // User to manage languages.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Check that the configure link exists.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertSession()->linkByHrefExists('admin/config/regional/language/detection/browser');

    // Check that defaults are loaded from language.mappings.yml.
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->assertSession()->fieldValueEquals('edit-mappings-zh-cn-browser-langcode', 'zh-cn');
    $this->assertSession()->fieldValueEquals('edit-mappings-zh-cn-drupal-langcode', 'zh-hans');

    // Delete zh-cn language code.
    $browser_langcode = 'zh-cn';
    $this->drupalGet('admin/config/regional/language/detection/browser/delete/' . $browser_langcode);
    $this->assertSession()->pageTextContains("Are you sure you want to delete {$browser_langcode}?");

    // Confirm the delete.
    $edit = [];
    $this->drupalGet('admin/config/regional/language/detection/browser/delete/' . $browser_langcode);
    $this->submitForm($edit, 'Confirm');

    $this->assertSession()->statusMessageContains("The mapping for the {$browser_langcode} browser language code has been deleted.", 'status');

    // Check we went back to the browser negotiation mapping overview.
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    // Check that Chinese browser language code no longer exists.
    $this->assertSession()->fieldNotExists('edit-mappings-zh-cn-browser-langcode');

    // Add a new custom mapping.
    $edit = [
      'new_mapping[browser_langcode]' => 'xx',
      'new_mapping[drupal_langcode]' => 'en',
    ];
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-browser-langcode', 'xx');
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-drupal-langcode', 'en');

    // Add the same custom mapping again.
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('Browser language codes must be unique.', 'error');

    // Change browser language code of our custom mapping to zh-sg.
    $edit = [
      'mappings[xx][browser_langcode]' => 'zh-sg',
      'mappings[xx][drupal_langcode]' => 'en',
    ];
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('Browser language codes must be unique.', 'error');

    // Change Drupal language code of our custom mapping to zh-hans.
    $edit = [
      'mappings[xx][browser_langcode]' => 'xx',
      'mappings[xx][drupal_langcode]' => 'zh-hans',
    ];
    $this->drupalGet('admin/config/regional/language/detection/browser');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-browser-langcode', 'xx');
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-drupal-langcode', 'zh-hans');
  }

}
