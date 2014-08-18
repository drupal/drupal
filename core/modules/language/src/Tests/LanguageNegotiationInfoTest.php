<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageNegotiationInfoTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;
use Drupal\simpletest\WebTestBase;

/**
 * Tests alterations to language types/negotiation info.
 *
 * @group language
 */
class LanguageNegotiationInfoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'view the administration theme'));
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/add', array('predefined_langcode' => 'it'), t('Add language'));
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
  function testInfoAlterations() {
    $this->stateSet(array(
      // Enable language_test type info.
      'language_test.language_types' => TRUE,
      // Enable language_test negotiation info (not altered yet).
      'language_test.language_negotiation_info' => TRUE,
      // Alter LanguageInterface::TYPE_CONTENT to be configurable.
      'language_test.content_language_type' => TRUE,
    ));
    $this->container->get('module_handler')->install(array('language_test'));
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
    $form_field = $type . '[enabled]['. $interface_method_id .']';
    $edit = array(
      $form_field => TRUE,
      $type . '[enabled][' . $test_method_id . ']' => TRUE,
      $test_type . '[enabled][' . $test_method_id . ']' => TRUE,
      $test_type . '[configurable]' => TRUE,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Alter language negotiation info to remove interface language negotiation
    // method.
    $this->stateSet(array(
      'language_test.language_negotiation_info_alter' => TRUE,
    ));

    $negotiation = $this->container->get('config.factory')->get('language.types')->get('negotiation.' . $type . '.enabled');
    $this->assertFalse(isset($negotiation[$interface_method_id]), 'Interface language negotiation method removed from the stored settings.');

    $this->drupalGet('admin/config/regional/language/detection');
    $this->assertNoFieldByName($form_field, NULL, 'Interface language negotiation method unavailable.');

    // Check that type-specific language negotiation methods can be assigned
    // only to the corresponding language types.
    foreach ($this->languageManager()->getLanguageTypes() as $type) {
      $form_field = $type . '[enabled][test_language_negotiation_method_ts]';
      if ($type == $test_type) {
        $this->assertFieldByName($form_field, NULL, format_string('Type-specific test language negotiation method available for %type.', array('%type' => $type)));
      }
      else {
        $this->assertNoFieldByName($form_field, NULL, format_string('Type-specific test language negotiation method unavailable for %type.', array('%type' => $type)));
      }
    }

    // Check language negotiation results.
    $this->drupalGet('');
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $langcode = $last[$type];
      $value = $type == LanguageInterface::TYPE_CONTENT || strpos($type, 'test') !== FALSE ? 'it' : 'en';
      $this->assertEqual($langcode, $value, format_string('The negotiated language for %type is %language', array('%type' => $type, '%language' => $value)));
    }

    // Uninstall language_test and check that everything is set back to the
    // original status.
    $this->container->get('module_handler')->uninstall(array('language_test'));
    $this->rebuildContainer();

    // Check that only the core language types are available.
    foreach ($this->languageManager()->getDefinedLanguageTypes() as $type) {
      $this->assertTrue(strpos($type, 'test') === FALSE, format_string('The %type language is still available', array('%type' => $type)));
    }

    // Check that fixed language types are properly configured, even those
    // previously set to configurable.
    $this->checkFixedLanguageTypes();

    // Check that unavailable language negotiation methods are not present in
    // the negotiation settings.
    $negotiation = $this->container->get('config.factory')->get('language.types')->get('negotiation.' . $type . '.enabled');
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
        $negotiation = $this->container->get('config.factory')->get('language.types')->get('negotiation.' . $type . '.enabled');
        $equal = count($info['fixed']) == count($negotiation);
        while ($equal && list($id) = each($negotiation)) {
          list(, $info_id) = each($info['fixed']);
          $equal = $info_id == $id;
        }
        $this->assertTrue($equal, format_string('language negotiation for %type is properly set up', array('%type' => $type)));
      }
    }
  }
}
