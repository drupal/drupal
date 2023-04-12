<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests per-language block configuration.
 *
 * @group block
 */
class BlockLanguageTest extends BrowserTestBase {

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['language', 'block', 'content_translation', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer languages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Verify that language was added successfully.
    $this->assertSession()->pageTextContains('French');

    // Set path prefixes for both languages.
    $this->config('language.negotiation')->set('url', [
      'source' => 'path_prefix',
      'prefixes' => [
        'en' => 'en',
        'fr' => 'fr',
      ],
    ])->save();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();
  }

  /**
   * Tests the visibility settings for the blocks based on language.
   */
  public function testLanguageBlockVisibility() {
    // Check if the visibility setting is available.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    // Ensure that the language visibility field is visible without a type
    // setting.
    $this->assertSession()->fieldExists('visibility[language][langcodes][en]');
    $this->assertSession()->fieldNotExists('visibility[language][context_mapping][language]');

    // Enable a standard block and set the visibility setting for one language.
    $edit = [
      'visibility[language][langcodes][en]' => TRUE,
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
    ];
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Change the default language.
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');

    // Check that a page has a block.
    $this->drupalGet('en');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Powered by Drupal');

    // Check that a page doesn't has a block for the current language anymore.
    $this->drupalGet('fr');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
  }

  /**
   * Tests if the visibility settings are removed if the language is deleted.
   */
  public function testLanguageBlockVisibilityLanguageDelete() {
    // Enable a standard block and set the visibility setting for one language.
    $edit = [
      'visibility' => [
        'language' => [
          'langcodes' => [
            'fr' => 'fr',
          ],
          'context_mapping' => ['language' => '@language.current_language_context:language_interface'],
        ],
      ],
    ];
    $block = $this->drupalPlaceBlock('system_powered_by_block', $edit);

    // Check that we have the language in config after saving the setting.
    $visibility = $block->getVisibility();
    $this->assertEquals('fr', $visibility['language']['langcodes']['fr'], 'Language is set in the block configuration.');

    // Delete the language.
    $this->drupalGet('admin/config/regional/language/delete/fr');
    $this->submitForm([], 'Delete');

    // Check that the language is no longer stored in the configuration after
    // it is deleted.
    $block = Block::load($block->id());
    $visibility = $block->getVisibility();
    $this->assertArrayNotHasKey('language', $visibility, 'Language is no longer not set in the block configuration after deleting the block.');

    // Ensure that the block visibility for language is gone from the UI.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Configure');
    $this->assertSession()->elementNotExists('xpath', '//details[@id="edit-visibility-language"]');
  }

  /**
   * Tests block language visibility with different language types.
   */
  public function testMultipleLanguageTypes() {
    // Customize content language detection to be different from interface
    // language detection.
    $edit = [
      // Interface language detection: only using session.
      'language_interface[enabled][language-url]' => FALSE,
      'language_interface[enabled][language-session]' => TRUE,
      // Content language detection: only using URL.
      'language_content[configurable]' => TRUE,
      'language_content[enabled][language-url]' => TRUE,
      'language_content[enabled][language-interface]' => FALSE,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Check if the visibility setting is available with a type setting.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    $this->assertSession()->fieldExists('visibility[language][langcodes][en]');
    $this->assertSession()->fieldExists('visibility[language][context_mapping][language]');

    // Enable a standard block and set visibility to French only.
    $block_id = strtolower($this->randomMachineName(8));
    $edit = [
      'visibility[language][context_mapping][language]' => '@language.current_language_context:language_interface',
      'visibility[language][langcodes][fr]' => TRUE,
      'id' => $block_id,
      'region' => 'sidebar_first',
    ];
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Interface negotiation depends on request arguments.
    $this->drupalGet('node/1', ['query' => ['language' => 'en']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->drupalGet('node/1', ['query' => ['language' => 'fr']]);
    $this->assertSession()->pageTextContains('Powered by Drupal');

    // Log in again in order to clear the interface language stored in the
    // session.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Content language does not depend on session/request arguments.
    // It will fall back on English (site default) and not display the block.
    $this->drupalGet('en');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->drupalGet('fr');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');

    // Change visibility to now depend on content language for this block.
    $edit = [
      'visibility[language][context_mapping][language]' => '@language.current_language_context:language_content',
    ];
    $this->drupalGet('admin/structure/block/manage/' . $block_id);
    $this->submitForm($edit, 'Save block');

    // Content language negotiation does not depend on request arguments.
    // It will fall back on English (site default) and not display the block.
    $this->drupalGet('node/1', ['query' => ['language' => 'en']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->drupalGet('node/1', ['query' => ['language' => 'fr']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Powered by Drupal');

    // Content language negotiation depends on path prefix.
    $this->drupalGet('en');
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->drupalGet('fr');
    $this->assertSession()->pageTextContains('Powered by Drupal');
  }

}
