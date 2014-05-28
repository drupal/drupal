<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\CacheDecoratorLanguageTest.
 */

namespace Drupal\system\Tests\Plugin;

use Drupal\Core\Language\Language;
use Drupal\plugin_test\Plugin\CachedMockBlockManager;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that the AlterDecorator fires and respects the alter hook.
 */
class CacheDecoratorLanguageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('plugin_test', 'locale', 'language');

  public static function getInfo() {
    return array(
      'name' => 'CacheDecoratorLanguage',
      'description' => 'Tests that the CacheDecorator stores definitions by language appropriately.',
      'group' => 'Plugin API',
    );
  }

  public function setUp() {
    parent::setUp();

    // Populate sample definitions.
    $this->mockBlockExpectedDefinitions = array(
      'user_login' => array(
        'label' => 'User login',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock',
      ),
      'menu:main_menu' => array(
        'label' => 'Main menu',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
      ),
      'menu:navigation' => array(
        'label' => 'Navigation',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
      ),
      'layout' => array(
        'label' => 'Layout',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlock',
      ),
      'layout:foo' => array(
        'label' => 'Layout Foo',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlock',
      ),
    );

    // Create two languages: Spanish and German.
    $this->languages = array('de', 'es');
    foreach ($this->languages as $langcode) {
      $language = new Language(array('id' => $langcode));
      $languages[$langcode] = language_save($language);
      // Set up translations for each mock block label.
      $custom_strings = array();
      foreach ($this->mockBlockExpectedDefinitions as $definition) {
        $custom_strings[$definition['label']] = $langcode . ' ' . $definition['label'];
      }
      $this->addCustomTranslations($langcode, array('' => $custom_strings));
      $this->rebuildContainer();
    }
    // Write test settings.php with new translations.
    $this->writeCustomTranslations();
  }

  /**
   * Check the translations of the cached plugin definitions.
   */
  public function testCacheDecoratorLanguage() {
    $languages = $this->languages;
    $this->drupalGet('plugin_definition_test');
    foreach ($this->mockBlockExpectedDefinitions as $definition) {
      // Find our source text.
      $this->assertText($definition['label']);
    }
    foreach ($languages as $langcode) {
      $url = $langcode . '/plugin_definition_test';
      // For each language visit the language specific version of the page again.
      $this->drupalGet($url);
      foreach ($this->mockBlockExpectedDefinitions as $definition) {
        // Find our provided translations.
        $label = $langcode . ' ' . $definition['label'];
        $this->assertText($label);
      }
    }
    // Manually check that the expected cache keys are present.
    $languages[] = 'en';
    foreach ($languages as $langcode) {
      $cache = \Drupal::cache()->get('mock_block:' . $langcode);
      $this->assertEqual($cache->cid, 'mock_block:' . $langcode, format_string('The !cache cache exists.', array('!cache' => 'mock_block:' . $langcode)));
      $this->assertEqual($cache->expire, 1542646800, format_string('The cache expiration was properly set.'));
    }
    // Clear the plugin definitions.
    $manager = new CachedMockBlockManager();
    $manager->clearCachedDefinitions();
    foreach ($languages as $langcode) {
      $cache = \Drupal::cache()->get('mock_block:' . $langcode);
      $this->assertFalse($cache, format_string('The !cache cache was properly cleared through the cache::deleteTags() method.', array('!cache' => 'mock_block:' . $langcode)));
    }
    // Change the translations for the german language and recheck strings.
    $custom_strings = array();
    foreach ($this->mockBlockExpectedDefinitions as $definition) {
      $custom_strings[$definition['label']] = $definition['label'] . ' de';
    }
    $this->addCustomTranslations('de', array('' => $custom_strings));
    $this->writeCustomTranslations();
    $this->drupalGet('de/plugin_definition_test');
    foreach ($this->mockBlockExpectedDefinitions as $definition) {
      // Find our provided translations.
      $label = $definition['label'] . ' de';
      $this->assertText($label);
    }
  }

}
