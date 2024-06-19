<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests alterations to language types/negotiation info.
 *
 * @group language
 */
class LanguageNegotiationInfoTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'view the administration theme',
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'it'], 'Add language');
  }

  /**
   * Returns the configurable language manager.
   *
   * @return \Drupal\language\ConfigurableLanguageManager
   */
  protected function languageManager() {
    return $this->container->get('language_manager');
  }

  /**
   * Sets state flags for language_test module.
   *
   * Ensures to correctly update data both in the child site and the test runner
   * environment.
   *
   * @param array $values
   *   The key/value pairs to set in state.
   */
  protected function stateSet(array $values) {
    // Set the new state values.
    $this->container->get('state')->setMultiple($values);
    // Refresh in-memory static state/config caches and static variables.
    $this->refreshVariables();
    // Refresh/rewrite language negotiation configuration, in order to pick up
    // the manipulations performed by language_test module's info alter hooks.
    $this->container->get('language_negotiator')->purgeConfiguration();
  }

  /**
   * Tests alterations to language types/negotiation info.
   */
  public function testInfoAlterations(): void {
    $this->stateSet([
      // Enable language_test type info.
      'language_test.language_types' => TRUE,
      // Enable language_test negotiation info (not altered yet).
      'language_test.language_negotiation_info' => TRUE,
      // Alter LanguageInterface::TYPE_CONTENT to be configurable.
      'language_test.content_language_type' => TRUE,
    ]);
    $this->container->get('module_installer')->install(['language_test']);
    $this->resetAll();

    // Check that fixed language types are properly configured without the need
    // of saving the language negotiation settings.
    $this->checkFixedLanguageTypes();

    $type = LanguageInterface::TYPE_CONTENT;
    $language_types = $this->languageManager()->getLanguageTypes();
    $this->assertContains($type, $language_types, 'Content language type is configurable.');

    // Enable some core and custom language negotiation methods. The test
    // language type is supposed to be configurable.
    $test_type = 'test_language_type';
    $interface_method_id = LanguageNegotiationUI::METHOD_ID;
    $test_method_id = 'test_language_negotiation_method';
    $form_field = $type . '[enabled][' . $interface_method_id . ']';
    $edit = [
      $form_field => TRUE,
      $type . '[enabled][' . $test_method_id . ']' => TRUE,
      $test_type . '[enabled][' . $test_method_id . ']' => TRUE,
      $test_type . '[configurable]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Alter language negotiation info to remove interface language negotiation
    // method.
    $this->stateSet([
      'language_test.language_negotiation_info_alter' => TRUE,
    ]);

    $negotiation = $this->config('language.types')->get('negotiation.' . $type . '.enabled');
    $this->assertFalse(isset($negotiation[$interface_method_id]), 'Interface language negotiation method removed from the stored settings.');

    // Check that the interface language negotiation method is unavailable.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertSession()->fieldNotExists($form_field);

    // Check that type-specific language negotiation methods can be assigned
    // only to the corresponding language types.
    foreach ($this->languageManager()->getLanguageTypes() as $type) {
      $form_field = $type . '[enabled][test_language_negotiation_method_ts]';
      if ($type == $test_type) {
        $this->assertSession()->fieldExists($form_field);
      }
      else {
        $this->assertSession()->fieldNotExists($form_field);
      }
    }

    // Check language negotiation results.
    $this->drupalGet('');
    $last = \Drupal::keyValue('language_test')->get('language_negotiation_last');
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $langcode = $last[$type];
      $value = $type == LanguageInterface::TYPE_CONTENT || str_contains($type, 'test') ? 'it' : 'en';
      $this->assertEquals($langcode, $value, "The negotiated language for $type is $value");
    }

    // Uninstall language_test and check that everything is set back to the
    // original status.
    $this->container->get('module_installer')->uninstall(['language_test']);
    $this->rebuildContainer();

    // Check that only the core language types are available.
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $this->assertStringNotContainsString('test', $type, "The $type language is still available");
    }

    // Check that fixed language types are properly configured, even those
    // previously set to configurable.
    $this->checkFixedLanguageTypes();

    // Check that unavailable language negotiation methods are not present in
    // the negotiation settings.
    $negotiation = $this->config('language.types')->get('negotiation.' . $type . '.enabled');
    $this->assertFalse(isset($negotiation[$test_method_id]), 'The disabled test language negotiation method is not part of the content language negotiation settings.');

    // Check that configuration page presents the correct options and settings.
    $this->assertSession()->pageTextNotContains("Test language detection");
    $this->assertSession()->pageTextNotContains("This is a test language negotiation method");
  }

  /**
   * Check that language negotiation for fixed types matches the stored one.
   */
  protected function checkFixedLanguageTypes() {
    $configurable = $this->languageManager()->getLanguageTypes();
    foreach ($this->languageManager()->getDefinedLanguageTypesInfo() as $type => $info) {
      if (!in_array($type, $configurable) && isset($info['fixed'])) {
        $negotiation = $this->config('language.types')->get('negotiation.' . $type . '.enabled');
        $equal = array_keys($negotiation) === array_values($info['fixed']);
        $this->assertTrue($equal, "language negotiation for $type is properly set up");
      }
    }
  }

  /**
   * Tests altering config of configurable language types.
   */
  public function testConfigLangTypeAlterations(): void {
    // Default of config.
    $test_type = LanguageInterface::TYPE_CONTENT;
    $this->assertFalse($this->isLanguageTypeConfigurable($test_type), 'Language type is not configurable.');

    // Editing config.
    $edit = [$test_type . '[configurable]' => TRUE];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');
    $this->assertTrue($this->isLanguageTypeConfigurable($test_type), 'Language type is now configurable.');

    // After installing another module, the config should be the same.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[test_module][enable]' => 1], 'Install');
    $this->assertTrue($this->isLanguageTypeConfigurable($test_type), 'Language type is still configurable.');

    // After uninstalling the other module, the config should be the same.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[test_module]' => 1], 'Uninstall');
    $this->assertTrue($this->isLanguageTypeConfigurable($test_type), 'Language type is still configurable.');
  }

  /**
   * Checks whether the given language type is configurable.
   *
   * @param string $type
   *   The language type.
   *
   * @return bool
   *   TRUE if the specified language type is configurable, FALSE otherwise.
   */
  protected function isLanguageTypeConfigurable($type) {
    $configurable_types = $this->config('language.types')->get('configurable');
    return in_array($type, $configurable_types);
  }

}
