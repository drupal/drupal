<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleLocaleLookupTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests LocaleLookup.
 *
 * @group locale
 */
class LocaleLocaleLookupTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'locale_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Change the language default object to different values.
    $language = ConfigurableLanguage::createFromLangcode('fr');
    $language->set('default', TRUE);
    $language->save();
    $this->drupalLogin($this->root_user);
  }

  /**
   * Tests that there are no circular dependencies.
   */
  public function testCircularDependency() {
    // Ensure that we can enable early_translation_test on a non-english site.
    $this->drupalPostForm('admin/modules', array('modules[Testing][early_translation_test][enable]' => TRUE), t('Save configuration'));
    $this->assertResponse(200);
  }

  /**
   * Test language fallback defaults.
   */
  public function testLanguageFallbackDefaults() {
    $this->drupalGet('');
    // Ensure state of fallback languages persisted by
    // locale_test_language_fallback_candidates_locale_lookup_alter() is empty.
    $this->assertEqual(\Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_candidates'), array());
    // Make sure there is enough information provided for alter hooks.
    $context = \Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_context');
    $this->assertEqual($context['langcode'], 'fr');
    $this->assertEqual($context['operation'], 'locale_lookup');
  }

}
