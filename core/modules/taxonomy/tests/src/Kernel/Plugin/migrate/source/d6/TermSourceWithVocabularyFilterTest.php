<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

/**
 * Tests the taxonomy term source with vocabulary filter.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\Term
 * @group taxonomy
 */
class TermSourceWithVocabularyFilterTest extends TermTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    // Get the source data from parent.
    $tests = parent::providerSource();

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
        'parent' => [1],
      ],
    ];

    // We know there are two rows with vid == 5.
    $tests[0]['expected_count'] = 2;

    // Set up source plugin configuration.
    $tests[0]['configuration'] = [
      'bundle' => [5],
    ];

    return $tests;
  }

}
