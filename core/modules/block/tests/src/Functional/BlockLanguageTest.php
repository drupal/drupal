<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests if a block can be configured to be only visible on a particular
 * language.
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
  public static $modules = ['language', 'block', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer blocks', 'administer languages']);
    $this->drupalLogin($this->adminUser);

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('French', 'Language added successfully.');
  }

  /**
   * Tests the visibility settings for the blocks based on language.
   */
  public function testLanguageBlockVisibility() {
    // Check if the visibility setting is available.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);

    $this->assertField('visibility[language][langcodes][en]', 'Language visibility field is visible.');
    $this->assertNoField('visibility[language][context_mapping][language]', 'Language type field is not visible.');

    // Enable a standard block and set the visibility setting for one language.
    $edit = [
      'visibility[language][langcodes][en]' => TRUE,
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
    ];
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme, $edit, t('Save block'));

    // Change the default language.
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language', $edit, t('Save configuration'));

    // Check that a page has a block.
    $this->drupalGet('en');
    $this->assertText('Powered by Drupal', 'The body of the custom block appears on the page.');

    // Check that a page doesn't has a block for the current language anymore.
    $this->drupalGet('fr');
    $this->assertNoText('Powered by Drupal', 'The body of the custom block does not appear on the page.');
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
    $this->assertEqual('fr', $visibility['language']['langcodes']['fr'], 'Language is set in the block configuration.');

    // Delete the language.
    $this->drupalPostForm('admin/config/regional/language/delete/fr', [], t('Delete'));

    // Check that the language is no longer stored in the configuration after
    // it is deleted.
    $block = Block::load($block->id());
    $visibility = $block->getVisibility();
    $this->assertTrue(empty($visibility['language']['langcodes']['fr']), 'Language is no longer not set in the block configuration after deleting the block.');

    // Ensure that the block visibility for language is gone from the UI.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Configure');
    $elements = $this->xpath('//details[@id="edit-visibility-language"]');
    $this->assertTrue(empty($elements));
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
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Check if the visibility setting is available with a type setting.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    $this->assertField('visibility[language][langcodes][en]', 'Language visibility field is visible.');
    $this->assertField('visibility[language][context_mapping][language]', 'Language type field is visible.');

    // Enable a standard block and set visibility to French only.
    $block_id = strtolower($this->randomMachineName(8));
    $edit = [
      'visibility[language][context_mapping][language]' => '@language.current_language_context:language_interface',
      'visibility[language][langcodes][fr]' => TRUE,
      'id' => $block_id,
      'region' => 'sidebar_first',
    ];
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme, $edit, t('Save block'));

    // Interface negotiation depends on request arguments.
    $this->drupalGet('node', ['query' => ['language' => 'en']]);
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');
    $this->drupalGet('node', ['query' => ['language' => 'fr']]);
    $this->assertText('Powered by Drupal', 'The body of the block appears on the page.');

    // Log in again in order to clear the interface language stored in the
    // session.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Content language does not depend on session/request arguments.
    // It will fall back on English (site default) and not display the block.
    $this->drupalGet('en');
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');
    $this->drupalGet('fr');
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');

    // Change visibility to now depend on content language for this block.
    $edit = [
      'visibility[language][context_mapping][language]' => '@language.current_language_context:language_content',
    ];
    $this->drupalPostForm('admin/structure/block/manage/' . $block_id, $edit, t('Save block'));

    // Content language negotiation does not depend on request arguments.
    // It will fall back on English (site default) and not display the block.
    $this->drupalGet('node', ['query' => ['language' => 'en']]);
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');
    $this->drupalGet('node', ['query' => ['language' => 'fr']]);
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');

    // Content language negotiation depends on path prefix.
    $this->drupalGet('en');
    $this->assertNoText('Powered by Drupal', 'The body of the block does not appear on the page.');
    $this->drupalGet('fr');
    $this->assertText('Powered by Drupal', 'The body of the block appears on the page.');
  }

}
