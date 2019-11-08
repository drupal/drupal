<?php

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
  public static $modules = ['block', 'language', 'path'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $web_user = $this->drupalCreateUser(['administer languages', 'access administration pages', 'administer site configuration']);
    $this->drupalLogin($web_user);

    // Enable French language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Make French the default language.
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language', $edit, t('Save configuration'));

    // Delete English.
    $this->drupalPostForm('admin/config/regional/language/delete/en', [], t('Delete'));

    // Changing the default language causes a container rebuild. Therefore need
    // to rebuild the container in the test environment.
    $this->rebuildContainer();

    // Verify that French is the only language.
    $this->container->get('language_manager')->reset();
    $this->assertFalse(\Drupal::languageManager()->isMultilingual(), 'Site is mono-lingual');
    $this->assertEqual(\Drupal::languageManager()->getDefaultLanguage()->getId(), 'fr', 'French is the default language');

    // Set language detection to URL.
    $edit = ['language_interface[enabled][language-url]' => TRUE];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Verifies that links do not have language prefixes in them.
   */
  public function testPageLinks() {
    // Navigate to 'admin/config' path.
    $this->drupalGet('admin/config');

    // Verify that links in this page do not have a 'fr/' prefix.
    $this->assertNoLinkByHref('/fr/', 'Links do not contain language prefix');

    // Verify that links in this page can be followed and work.
    $this->clickLink(t('Languages'));
    $this->assertResponse(200, 'Clicked link results in a valid page');
    $this->assertText(t('Add language'), 'Page contains the add language text');
  }

}
