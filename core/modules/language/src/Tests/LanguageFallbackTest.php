<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageFallbackTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the language fallback behavior.
 */
class LanguageFallbackTest extends LanguageTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Language fallback',
      'description' => 'Tests the language fallback behavior.',
      'group' => 'Language',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $i = 0;
    foreach (array('af', 'am', 'ar') as $langcode) {
      $language = new Language(array(
        'id' => $langcode,
        'weight' => $i--,
      ));
      language_save($language);
    }
  }

  /**
   * Tests language fallback candidates.
   */
  public function testCandidates() {
    $language_list = $this->languageManager->getLanguages();
    $expected = array_keys($language_list + array(LanguageInterface::LANGCODE_NOT_SPECIFIED => NULL));

    // Check that language fallback candidates by default are all the available
    // languages sorted by weight.
    $candidates = $this->languageManager->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are properly returned.');

    // Check that candidates are alterable.
    $this->state->set('language_test.fallback_alter.candidates', TRUE);
    $expected = array_slice($expected, 0, count($expected) - 1);
    $candidates = $this->languageManager->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are alterable.');

    // Check that candidates are alterable for specific operations.
    $this->state->set('language_test.fallback_alter.candidates', FALSE);
    $this->state->set('language_test.fallback_operation_alter.candidates', TRUE);
    $expected[] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $expected[] = LanguageInterface::LANGCODE_NOT_APPLICABLE;
    $candidates = $this->languageManager->getFallbackCandidates(NULL, array('operation' => 'test'));
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are alterable for specific operations.');

    // Check that when the site is monolingual no language fallback is applied.
    $default_langcode = $this->languageManager->getDefaultLanguage()->id;
    foreach ($language_list as $langcode => $language) {
      if ($langcode != $default_langcode) {
        language_delete($langcode);
      }
    }
    $candidates = $this->languageManager->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), array(LanguageInterface::LANGCODE_DEFAULT), 'Language fallback is not applied when the Language module is not enabled.');
  }

}
