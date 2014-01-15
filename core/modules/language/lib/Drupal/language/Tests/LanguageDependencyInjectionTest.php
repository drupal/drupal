<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageDependencyInjectionTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;

/**
 * Test for dependency injected language object.
 */
class LanguageDependencyInjectionTest extends LanguageTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Language dependency injection',
      'description' => 'Compares the default language from $GLOBALS against the dependency injected language object.',
      'group' => 'Language',
    );
  }

  /**
   * Test dependency injected languages against a new Language object.
   *
   * @see \Drupal\Core\Language\Language
   */
  function testDependencyInjectedNewLanguage() {
    // Initialize the language system.
    drupal_language_initialize();

    $expected = $this->languageManager->getDefaultLanguage();
    $result = $this->languageManager->getCurrentLanguage();
    foreach ($expected as $property => $value) {
      $this->assertEqual($expected->$property, $result->$property, format_string('The dependency injected language object %prop property equals the new Language object %prop property.', array('%prop' => $property)));
    }
  }

  /**
   * Test dependency injected Language object against a new default language
   * object.
   *
   * @see \Drupal\Core\Language\Language
   */
  function testDependencyInjectedNewDefaultLanguage() {
    // Change the language default object to different values.
    $new_language_default = array(
      'id' => 'fr',
      'name' => 'French',
      'direction' => 0,
      'weight' => 0,
      'method_id' => 'language-default',
      'default' => TRUE,
    );
    variable_set('language_default', $new_language_default);

    // Initialize the language system.
    $this->languageManager->init();

    // The language system creates a Language object which contains the
    // same properties as the new default language object.
    $expected = new Language($new_language_default);
    $result = $this->languageManager->getCurrentLanguage();
    foreach ($expected as $property => $value) {
      $this->assertEqual($expected->$property, $result->$property, format_string('The dependency injected language object %prop property equals the default language object %prop property.', array('%prop' => $property)));
    }

    // Delete the language_default variable we previously set.
    variable_del('language_default');
  }

}
