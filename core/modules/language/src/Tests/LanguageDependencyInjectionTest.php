<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageDependencyInjectionTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\language\Exception\DeleteDefaultLanguageException;

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
   * @see \Drupal\Core\Language\LanguageInterface
   */
  function testDependencyInjectedNewLanguage() {
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
    $default_language = \Drupal::languageManager()->getDefaultLanguage();
    // Change the language default object to different values.
    $new_language_default = new Language(array(
      'id' => 'fr',
      'name' => 'French',
      'direction' => 0,
      'weight' => 0,
      'method_id' => 'language-default',
      'default' => TRUE,
    ));
    language_save($new_language_default);

    // The language system creates a Language object which contains the
    // same properties as the new default language object.
    $result = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertIdentical($result->id, 'fr');

    // Delete the language to check that we fallback to the default.
    try {
      language_delete('fr');
      $this->fail('Expected DeleteDefaultLanguageException thrown.');
    }
    catch (DeleteDefaultLanguageException $e) {
      $this->pass('Expected DeleteDefaultLanguageException thrown.');
    }

    // Re-save the previous default language and the delete should work.
    language_save($default_language);
    language_delete('fr');
    $result = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertIdentical($result->id, $default_language->id);
  }

}
