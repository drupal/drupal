<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\TermSourceWithVocabularyFilterTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

/**
 * Tests the Drupal 6 taxonomy term source with vocabulary filter.
 *
 * @group migrate_drupal
 */
class TermSourceWithVocabularyFilterTest extends TermTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->migrationConfiguration['source']['vocabulary'] = array(5);
    parent::setUp();
    $this->expectedResults = array_values(array_filter($this->expectedResults, function($result) {
      return $result['vid'] == 5;
    }));
    // We know there are two rows with vid == 5.
    $this->expectedCount = 2;
  }

}
