<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\field\Plugin\migrate\source\d6\FieldInstanceOptionTranslation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the field instance option translation source plugin.
 */
#[CoversClass(FieldInstanceOptionTranslation::class)]
#[Group('migrate_drupal')]
#[RunTestsInSeparateProcesses]
class FieldInstanceOptionTranslationTest extends FieldOptionTranslationTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $test = parent::providerSource();

    // FieldInstanceOptionTranslation extends FieldOptionTranslation so the
    // same test can be used with the addition of the 'type' field to the
    // output.
    $test[0]['expected_data'][0]['type'] = 'text';
    $test[0]['expected_data'][1]['type'] = 'text';
    $test[0]['expected_data'][2]['type'] = 'number_integer';
    $test[0]['expected_data'][3]['type'] = 'number_integer';
    return $test;
  }

}
