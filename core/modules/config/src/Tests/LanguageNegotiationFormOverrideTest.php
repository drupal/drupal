<?php

/**
 * @file
 * Contains \Drupal\config\Tests\LanguageNegotiationFormOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests language-negotiation overrides are not on language-negotiation form.
 *
 * @group config
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class LanguageNegotiationFormOverrideTest extends WebTestBase {

  public static $modules = array('language', 'locale');

  /**
   * Tests that overrides do not affect language-negotiation form values.
   */
  public function testFormWithOverride() {
    $this->drupalLogin($this->rootUser);
    $overridden_value_en = 'whatever';
    $overridden_value_es = 'loquesea';

    // Set up an override.
    $settings['config']['language.negotiation']['url']['prefixes'] = (object) array(
      'value' => array('en' => $overridden_value_en, 'es' => $overridden_value_es),
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'es',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Overridden string for language-negotiation should not exist in the form.
    $this->drupalGet('admin/config/regional/language/detection/url');

    // The language-negotiation form should be found.
    $this->assertText('Path prefix configuration', 'Language-negotiation form found for English.');

    // The English override should not be found.
    $this->assertNoFieldByName('prefix[en]', $overridden_value_en, 'Language-negotiation config override not found in English.');

    // Now check the Spanish version of the page for the same thing.
    $this->drupalGet($overridden_value_es . '/admin/config/regional/language/detection/url');

    // The language-negotiation form should be found.
    $this->assertText('Path prefix configuration', 'Language-negotiation form found for Spanish using the overridden prefix.');

    // The Spanish override should not be found.
    $this->assertNoFieldByName('prefix[es]', $overridden_value_es, 'Language-negotiation config override not found in Spanish.');

  }

}
