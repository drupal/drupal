<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageNegotiationInfoTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for language types/negotiation info.
 */
class LanguageNegotiationInfoTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Language negotiation info',
      'description' => 'Tests alterations to language types/negotiation info.',
      'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp('language');
    require_once DRUPAL_ROOT .'/core/includes/language.inc';
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'view the administration theme'));
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/regional/language/add', array('predefined_langcode' => 'it'), t('Add language'));
  }

  /**
   * Tests alterations to language types/negotiation info.
   */
  function testInfoAlterations() {
    // Enable language type/negotiation info alterations.
    variable_set('language_test_language_types', TRUE);
    variable_set('language_test_language_negotiation_info', TRUE);
    $this->languageNegotiationUpdate();

    // Check that fixed language types are properly configured without the need
    // of saving the language negotiation settings.
    $this->checkFixedLanguageTypes();

    // Make the content language type configurable by updating the language
    // negotiation settings with the proper flag enabled.
    variable_set('language_test_content_language_type', TRUE);
    $this->languageNegotiationUpdate();
    $type = LANGUAGE_TYPE_CONTENT;
    $language_types = variable_get('language_types', language_types_get_default());
    $this->assertTrue($language_types[$type], t('Content language type is configurable.'));

    // Enable some core and custom language negotiation methods. The test
    // language type is supposed to be configurable.
    $test_type = 'test_language_type';
    $interface_method_id = LANGUAGE_NEGOTIATION_INTERFACE;
    $test_method_id = 'test_language_negotiation_method';
    $form_field = $type . '[enabled]['. $interface_method_id .']';
    $edit = array(
      $form_field => TRUE,
      $type . '[enabled][' . $test_method_id . ']' => TRUE,
      $test_type . '[enabled][' . $test_method_id . ']' => TRUE,
    );
    $this->drupalPost('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Remove the interface language negotiation method by updating the language
    // negotiation settings with the proper flag enabled.
    variable_set('language_test_language_negotiation_info_alter', TRUE);
    $this->languageNegotiationUpdate();
    $negotiation = variable_get("language_negotiation_$type", array());
    $this->assertFalse(isset($negotiation[$interface_method_id]), t('Interface language negotiation method removed from the stored settings.'));
    $this->assertNoFieldByXPath("//input[@name=\"$form_field\"]", NULL, t('Interface language negotiation method unavailable.'));

    // Check that type-specific language negotiation methods can be assigned
    // only to the corresponding language types.
    foreach (language_types_get_configurable() as $type) {
      $form_field = $type . '[enabled][test_language_negotiation_method_ts]';
      if ($type == $test_type) {
        $this->assertFieldByXPath("//input[@name=\"$form_field\"]", NULL, t('Type-specific test language negotiation method available for %type.', array('%type' => $type)));
      }
      else {
        $this->assertNoFieldByXPath("//input[@name=\"$form_field\"]", NULL, t('Type-specific test language negotiation method unavailable for %type.', array('%type' => $type)));
      }
    }

    // Check language negotiation results.
    $this->drupalGet('');
    $last = variable_get('language_test_language_negotiation_last', array());
    foreach (language_types_get_all() as $type) {
      $langcode = $last[$type];
      $value = $type == LANGUAGE_TYPE_CONTENT || strpos($type, 'test') !== FALSE ? 'it' : 'en';
      $this->assertEqual($langcode, $value, t('The negotiated language for %type is %language', array('%type' => $type, '%language' => $langcode)));
    }

    // Disable language_test and check that everything is set back to the
    // original status.
    $this->languageNegotiationUpdate('disable');

    // Check that only the core language types are available.
    foreach (language_types_get_all() as $type) {
      $this->assertTrue(strpos($type, 'test') === FALSE, t('The %type language is still available', array('%type' => $type)));
    }

    // Check that fixed language types are properly configured, even those
    // previously set to configurable.
    $this->checkFixedLanguageTypes();

    // Check that unavailable language negotiation methods are not present in
    // the negotiation settings.
    $negotiation = variable_get("language_negotiation_$type", array());
    $this->assertFalse(isset($negotiation[$test_method_id]), t('The disabled test language negotiation method is not part of the content language negotiation settings.'));

    // Check that configuration page presents the correct options and settings.
    $this->assertNoRaw(t('Test language detection'), t('No test language type configuration available.'));
    $this->assertNoRaw(t('This is a test language negotiation method'), t('No test language negotiation method available.'));
  }

  /**
   * Update language types/negotiation information.
   *
   * Manually invoke language_modules_enabled()/language_modules_disabled()
   * since they would not be invoked after enabling/disabling language_test the
   * first time.
   */
  protected function languageNegotiationUpdate($op = 'enable') {
    static $last_op = NULL;
    $modules = array('language_test');

    // Enable/disable language_test only if we did not already before.
    if ($last_op != $op) {
      $function = "module_{$op}";
      $function($modules);
      // Reset hook implementation cache.
      module_implements_reset();
    }

    drupal_static_reset('language_types_info');
    drupal_static_reset('language_negotiation_info');
    $function = "language_modules_{$op}d";
    if (function_exists($function)) {
      $function($modules);
    }

    $this->drupalGet('admin/config/regional/language/detection');
  }

  /**
   * Check that language negotiation for fixed types matches the stored one.
   */
  protected function checkFixedLanguageTypes() {
    drupal_static_reset('language_types_info');
    foreach (language_types_info() as $type => $info) {
      if (isset($info['fixed'])) {
        $negotiation = variable_get("language_negotiation_$type", array());
        $equal = count($info['fixed']) == count($negotiation);
        while ($equal && list($id) = each($negotiation)) {
          list(, $info_id) = each($info['fixed']);
          $equal = $info_id == $id;
        }
        $this->assertTrue($equal, t('language negotiation for %type is properly set up', array('%type' => $type)));
      }
    }
  }
}
