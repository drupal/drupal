<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests language-negotiation overrides are not on language-negotiation form.
 *
 * @group config
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class LanguageNegotiationFormOverrideTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'locale', 'locale_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that overrides do not affect language-negotiation form values.
   */
  public function testFormWithOverride(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer languages',
      'view the administration theme',
    ]));
    $overridden_value_en = 'whatever';
    $overridden_value_es = 'loquesea';

    // Set up an override.
    $settings['config']['language.negotiation']['url']['prefixes'] = (object) [
      'value' => ['en' => $overridden_value_en, 'es' => $overridden_value_es],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'es',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Overridden string for language-negotiation should not exist in the form.
    $this->drupalGet('admin/config/regional/language/detection/url');

    // The language-negotiation form should be found.
    $this->assertSession()->pageTextContains('Path prefix configuration');

    // The English override should not be found.
    $this->assertSession()->fieldValueNotEquals('prefix[en]', $overridden_value_en);

    // Now check the Spanish version of the page for the same thing.
    $this->drupalGet($overridden_value_es . '/admin/config/regional/language/detection/url');

    // The language-negotiation form should be found.
    $this->assertSession()->pageTextContains('Path prefix configuration');

    // The Spanish override should not be found.
    $this->assertSession()->fieldValueNotEquals('prefix[es]', $overridden_value_es);

  }

}
