<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageDependencyInjectionTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Test for dependency injected language object.
 */
class LanguageDependencyInjectionTest extends WebTestBase {
  public static function getInfo() {
    return array(
        'name' => 'Language dependency injection',
        'description' => 'Compares the default language from $GLOBALS against the dependency injected language object.',
        'group' => 'Language',
    );
  }

  function setUp() {
    parent::setUp('language');

    // Set up a new container to ensure we are building a new Language object
    // for each test.
    drupal_container(new ContainerBuilder());
  }

  /**
   * Test dependency injected Language against the GLOBAL language object.
   *
   * @todo Once the PHP global is gone, we won't need this test as the same
   * test is done without the PHP global in the following test.
   */
  function testDependencyInjectedLanguage() {
    // Initialize the language system.
    drupal_language_initialize();

    $expected = $GLOBALS[LANGUAGE_TYPE_INTERFACE];
    $result = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
    foreach ($expected as $property => $value) {
      $this->assertEqual($expected->$property, $result->$property, t('The dependency injected language object %prop property equals the $GLOBAL language object %prop property.', array('%prop' => $property)));
    }
  }

  /**
   * Test dependency injected languages against a new Language object.
   *
   * @see Drupal\Core\Language\Language
   */
  function testDependencyInjectedNewLanguage() {
    // Initialize the language system.
    drupal_language_initialize();

    $expected = language_default();
    $result = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
    foreach ($expected as $property => $value) {
      $this->assertEqual($expected->$property, $result->$property, t('The dependency injected language object %prop property equals the new Language object %prop property.', array('%prop' => $property)));
    }
  }

  /**
   * Test dependency injected Language object against a new default language
   * object.
   *
   * @see Drupal\Core\Language\Language
   */
  function testDependencyInjectedNewDefaultLanguage() {
    // Change the language default object to different values.
    $new_language_default = array(
      'langcode' => 'fr',
      'name' => 'French',
      'direction' => 0,
      'weight' => 0,
      'default' => TRUE,
    );
    variable_set('language_default', $new_language_default);

    // Initialize the language system.
    drupal_language_initialize();

    // The langauge system creates a Language object which contains the
    // same properties as the new default language object.
    $expected = new Language($new_language_default);
    $result = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
    foreach ($expected as $property => $value) {
      $this->assertEqual($expected->$property, $result->$property, t('The dependency injected language object %prop property equals the default language object %prop property.', array('%prop' => $property)));
    }

    // Delete the language_default variable we previously set.
    variable_del('language_default');
  }
}
