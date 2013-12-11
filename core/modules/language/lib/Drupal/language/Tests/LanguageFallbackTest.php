<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageFallbackTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the language fallback behavior.
 */
class LanguageFallbackTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Language fallback',
      'description' => 'Tests the language fallback behavior.',
      'group' => 'Language',
    );
  }

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableModules(array('language', 'language_test'));
    $this->installConfig(array('language'));

    $this->state = $this->container->get('state');

    for ($i = 0; $i < 3; $i++) {
      $language = new Language();
      $language->id = $this->randomName(2);
      $language->weight = -$i;
      language_save($language);
    }
  }

  /**
   * Tests language fallback candidates.
   */
  public function testCandidates() {
    $manager = $this->getLanguageManager();
    $expected = array_keys(language_list() + array(Language::LANGCODE_NOT_SPECIFIED => NULL));

    // Check that language fallback candidates by default are all the available
    // languages sorted by weight.
    $candidates = $manager->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are properly returned.');

    // Check that candidates are alterable.
    $this->state->set('language_test.fallback_alter.candidates', TRUE);
    $expected = array_slice($expected, 0, count($expected) - 1);
    $candidates = $manager->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are alterable.');

    // Check that candidates are alterable for specific operations.
    $this->state->set('language_test.fallback_alter.candidates', FALSE);
    $this->state->set('language_test.fallback_operation_alter.candidates', TRUE);
    $expected[] = Language::LANGCODE_NOT_SPECIFIED;
    $expected[] = Language::LANGCODE_NOT_APPLICABLE;
    $candidates = $manager->getFallbackCandidates(NULL, array('operation' => 'test'));
    $this->assertEqual(array_values($candidates), $expected, 'Language fallback candidates are alterable for specific operations.');

    // Check that when the site is monolingual no language fallback is applied.
    $default_langcode = language_default()->id;
    foreach (language_list() as $langcode => $language) {
      if ($langcode != $default_langcode) {
        language_delete($langcode);
      }
    }
    $candidates = $this->getLanguageManager()->getFallbackCandidates();
    $this->assertEqual(array_values($candidates), array(Language::LANGCODE_DEFAULT), 'Language fallback is not applied when the Language module is not enabled.');
  }

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManager
   *   The language manager.
   */
  protected function getLanguageManager() {
    return $this->container->get('language_manager');
  }

}
