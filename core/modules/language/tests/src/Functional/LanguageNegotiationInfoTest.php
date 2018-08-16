<?php

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
  public static $modules = ['language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages', 'view the administration theme', 'administer modules']);
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'it'], t('Add language'));
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
  public function testInfoAlterations() {
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
    $this->assertTrue(in_array($type, $language_types), 'Content language type is configurable.');

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
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Alter language negotiation info to remove interface language negotiation
    // method.
    $this->stateSet([
      'language_test.language_negotiation_info_alter' => TRUE,
    ]);

    $negotiation = $this->config('language.types')->get('negotiation.' . $type . '.enabled');
    $this->assertFalse(isset($negotiation[$interface_method_id]), 'Interface language negotiation method removed from the stored settings.');

    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertNoFieldByName($form_field, NULL, 'Interface language negotiation method unavailable.');

    // Check that type-specific language negotiation methods can be assigned
    // only to the corresponding language types.
    foreach ($this->languageManager()->getLanguageTypes() as $type) {
      $form_field = $type . '[enabled][test_language_negotiation_method_ts]';
      if ($type == $test_type) {
        $this->assertFieldByName($form_field, NULL, format_string('Type-specific test language negotiation method available for %type.', ['%type' => $type]));
      }
      else {
        $this->assertNoFieldByName($form_field, NULL, format_string('Type-specific test language negotiation method unavailable for %type.', ['%type' => $type]));
      }
    }

    // Check language negotiation results.
    $this->drupalGet('');
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $langcode = $last[$type];
      $value = $type == LanguageInterface::TYPE_CONTENT || strpos($type, 'test') !== FALSE ? 'it' : 'en';
      $this->assertEqual($langcode, $value, format_string('The negotiated language for %type is %language', ['%type' => $type, '%language' => $value]));
    }

    // Uninstall language_test and check that everything is set back to the
    // original status.
    $this->container->get('module_installer')->uninstall(['language_test']);
    $this->rebuildContainer();

    // Check that only the core language types are available.
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $this->assertTrue(strpos($type, 'test') === FALSE, format_string('The %type language is still available', ['%type' => $type]));
    }

    // Check that fixed language types are properly configured, even those
    // previously set to configurable.
    $this->checkFixedLanguageTypes();

    // Check that unavailable language negotiation methods are not present in
    // the negotiation settings.
    $negotiation = $this->config('language.types')->get('negotiation.' . $type . '.enabled');
    $this->assertFalse(isset($negotiation[$test_method_id]), 'The disabled test language negotiation method is not part of the content language negotiation settings.');

    // Check that configuration page presents the correct options and settings.
    $this->assertNoRaw(t('Test language detection'), 'No test language type configuration available.');
    $this->assertNoRaw(t('This is a test language negotiation method'), 'No test language negotiation method available.');
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
        $this->assertTrue($equal, format_string('language negotiation for %type is properly set up', ['%type' => $type]));
      }
    }
  }

  /**
   * Tests altering config of configurable language types.
   */
  public function testConfigLangTypeAlterations() {
    // Default of config.
    $test_type = LanguageInterface::TYPE_CONTENT;
    $this->assertFalse($this->isLanguageTypeConfigurable($test_type), 'Language type is not configurable.');

    // Editing config.
    $edit = [$test_type . '[configurable]' => TRUE];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->assertTrue($this->isLanguageTypeConfigurable($test_type), 'Language type is now configurable.');

    // After installing another module, the config should be the same.
    $this->drupalPostForm('admin/modules', ['modules[test_module][enable]' => 1], t('Install'));
    $this->assertTrue($this->isLanguageTypeConfigurable($test_type), 'Language type is still configurable.');

    // After uninstalling the other module, the config should be the same.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[test_module]' => 1], t('Uninstall'));
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
