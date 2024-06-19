<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm that paths are not changed on monolingual non-English sites.
 *
 * @group language
 */
class LanguagePathMonolingualTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'language', 'path'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $web_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($web_user);

    // Enable French language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Make French the default language.
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');

    // Delete English.
    $this->drupalGet('admin/config/regional/language/delete/en');
    $this->submitForm([], 'Delete');

    // Changing the default language causes a container rebuild. Therefore need
    // to rebuild the container in the test environment.
    $this->rebuildContainer();

    // Verify that French is the only language.
    $this->container->get('language_manager')->reset();
    $this->assertFalse(\Drupal::languageManager()->isMultilingual(), 'Site is mono-lingual');
    $this->assertEquals('fr', \Drupal::languageManager()->getDefaultLanguage()->getId(), 'French is the default language');

    // Set language detection to URL.
    $edit = ['language_interface[enabled][language-url]' => TRUE];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Verifies that links do not have language prefixes in them.
   */
  public function testPageLinks(): void {
    // Navigate to 'admin/config' path.
    $this->drupalGet('admin/config');

    // Verify that links in this page do not have a 'fr/' prefix.
    $this->assertSession()->linkByHrefNotExists('/fr/', 'Links do not contain language prefix');

    // Verify that links in this page can be followed and work.
    $this->clickLink('Languages');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add language');
  }

}
