<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7\VocabularyTest;

/**
 * Tests i18ntaxonomy vocabulary setting source plugin.
 *
 * @covers \Drupal\language\Plugin\migrate\source\d7\LanguageContentSettingsTaxonomyVocabulary
 *
 * @group language
 */
class LanguageContentTaxonomyVocabularySettingsTest extends VocabularyTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'language', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    // Get the source data from parent.
    $tests = parent::providerSource();

    foreach ($tests as &$test) {
      // Add the extra columns provided by i18n_taxonomy.
      foreach ($test['source_data']['taxonomy_vocabulary'] as &$vocabulary) {
        $vocabulary['language'] = 'und';
        $vocabulary['i18n_mode'] = 2;
      }
      foreach ($test['expected_data'] as &$expected) {
        $expected['language'] = 'und';
        $expected['i18n_mode'] = 2;
      }
    }
    return $tests;
  }

}
