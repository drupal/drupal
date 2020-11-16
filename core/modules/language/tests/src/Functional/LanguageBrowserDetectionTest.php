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
   * Tests for adding, editing and deleting mappings between browser language
   * codes and Drupal language codes.
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
    $message = t('Are you sure you want to delete @browser_langcode?', [
      '@browser_langcode' => $browser_langcode,
    ]);
    $this->assertRaw($message);

    // Confirm the delete.
    $edit = [];
    $this->drupalPostForm('admin/config/regional/language/detection/browser/delete/' . $browser_langcode, $edit, 'Confirm');

    // We need raw here because %browser will add HTML.
    $t_args = [
      '%browser' => $browser_langcode,
    ];
    $this->assertRaw(t('The mapping for the %browser browser language code has been deleted.', $t_args));

    // Check we went back to the browser negotiation mapping overview.
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    // Check that Chinese browser language code no longer exists.
    $this->assertSession()->fieldNotExists('edit-mappings-zh-cn-browser-langcode');

    // Add a new custom mapping.
    $edit = [
      'new_mapping[browser_langcode]' => 'xx',
      'new_mapping[drupal_langcode]' => 'en',
    ];
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, 'Save configuration');
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-browser-langcode', 'xx');
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-drupal-langcode', 'en');

    // Add the same custom mapping again.
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, 'Save configuration');
    $this->assertText('Browser language codes must be unique.');

    // Change browser language code of our custom mapping to zh-sg.
    $edit = [
      'mappings[xx][browser_langcode]' => 'zh-sg',
      'mappings[xx][drupal_langcode]' => 'en',
    ];
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, 'Save configuration');
    $this->assertText('Browser language codes must be unique.');

    // Change Drupal language code of our custom mapping to zh-hans.
    $edit = [
      'mappings[xx][browser_langcode]' => 'xx',
      'mappings[xx][drupal_langcode]' => 'zh-hans',
    ];
    $this->drupalPostForm('admin/config/regional/language/detection/browser', $edit, 'Save configuration');
    $this->assertSession()->addressEquals(Url::fromRoute('language.negotiation_browser'));
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-browser-langcode', 'xx');
    $this->assertSession()->fieldValueEquals('edit-mappings-xx-drupal-langcode', 'zh-hans');
  }

}
