<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\taxonomy\Plugin\migrate\source\d6\Term;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the taxonomy term source with vocabulary filter.
 */
#[CoversClass(Term::class)]
#[Group('taxonomy')]
#[RunTestsInSeparateProcesses]
class TermSourceWithVocabularyFilterTest extends TermTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
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
