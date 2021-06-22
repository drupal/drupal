<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Exception\DeleteDefaultLanguageException;

/**
 * Compares the default language from $GLOBALS against the dependency injected
 * language object.
 *
 * @group language
 */
class LanguageDependencyInjectionTest extends LanguageTestBase {

  /**
   * Tests dependency injected languages against a new Language object.
   *
   * @see \Drupal\Core\Language\LanguageInterface
   */
  public function testDependencyInjectedNewLanguage() {
    $expected = $this->languageManager->getDefaultLanguage();
    $result = $this->languageManager->getCurrentLanguage();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests dependency injected Language object.
   *
   * @see \Drupal\Core\Language\Language
   */
  public function testDependencyInjectedNewDefaultLanguage() {
    $default_language = ConfigurableLanguage::load(\Drupal::languageManager()->getDefaultLanguage()->getId());
    // Change the language default object to different values.
    $fr = ConfigurableLanguage::createFromLangcode('fr');
    $fr->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    // The language system creates a Language object which contains the
    // same properties as the new default language object.
    $result = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertSame('fr', $result->getId());

    // Delete the language to check that we fallback to the default.
    try {
      $fr->delete();
      $this->fail('Expected DeleteDefaultLanguageException thrown.');
    }
    catch (DeleteDefaultLanguageException $e) {
      // Expected exception; just continue testing.
    }

    // Re-save the previous default language and the delete should work.
    $this->config('system.site')->set('default_langcode', $default_language->getId())->save();

    $fr->delete();
    $result = \Drupal::languageManager()->getCurrentLanguage();
    $this->assertSame($default_language->getId(), $result->getId());
  }

}
