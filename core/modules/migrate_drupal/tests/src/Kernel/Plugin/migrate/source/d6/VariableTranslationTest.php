<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the variable source plugin.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation
 *
 * @group migrate_drupal
 */
class VariableTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['i18n_variable'] = [
      [
        'name' => 'site_slogan',
        'language' => 'fr',
        'value' => 's:23:"fr - migrate is awesome";',
      ],
      [
        'name' => 'site_name',
        'language' => 'fr',
        'value' => 's:14:"fr - site name";',
      ],
      [
        'name' => 'site_slogan',
        'language' => 'mi',
        'value' => 's:23:"mi - migrate is awesome";',
      ],
      [
        'name' => 'site_name',
        'language' => 'mi',
        'value' => 's:14:"mi - site name";',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'language' => 'fr',
        'site_slogan' => 'fr - migrate is awesome',
        'site_name' => 'fr - site name',
      ],
      [
        'language' => 'mi',
        'site_slogan' => 'mi - migrate is awesome',
        'site_name' => 'mi - site name',
      ],
    ];

    // The expected count.
    $tests[0]['expected_count'] = NULL;

    // The migration configuration.
    $tests[0]['configuration']['variables'] = [
      'site_slogan',
      'site_name',
    ];

    return $tests;
  }

}
