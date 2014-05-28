<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\TermSourceWithVocabularyFilterTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

/**
 * Tests the Drupal 6 taxonomy term source with vocabulary filter.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class TermSourceWithVocabularyFilterTest extends TermTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 taxonomy term source with vocabulary filter functionality',
      'description' => 'Tests D6 taxonomy term source plugin with vocabulary filter.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['source']['vocabulary'] = array(5);
    parent::setUp();
    $this->expectedResults = array_values(array_filter($this->expectedResults, function($result) {
      return $result['vid'] == 5;
    }));
  }
}
