<?php

/**
 * @file
 * Contains \Drupal\Tests\taxonomy\Unit\Migrate\TermSourceWithVocabularyFilterTest.
 */

namespace Drupal\Tests\taxonomy\Unit\Migrate;

/**
 * Tests the taxonomy term source with vocabulary filter.
 *
 * @group taxonomy
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
