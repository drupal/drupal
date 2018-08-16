<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source\d8;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the config source plugin.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\d8\Config
 * @group migrate_drupal
 */
class ConfigTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $data = [];

    // The source database tables.
    $data[0]['source_data'] = [
      'config' => [
        [
          'collection' => 'language.af',
          'name' => 'user.settings',
          'data' => 'a:1:{s:9:"anonymous";s:14:"af - Anonymous";}',
        ],
        [
          'collection' => '',
          'name' => 'user.settings',
          'data' => 'a:1:{s:9:"anonymous";s:9:"Anonymous";}',
        ],
        [
          'collection' => 'language.de',
          'name' => 'user.settings',
          'data' => 'a:1:{s:9:"anonymous";s:14:"de - Anonymous";}',
        ],
        [
          'collection' => 'language.af',
          'name' => 'bar',
          'data' => 'b:0;',
        ],
      ],
    ];

    // The expected results.
    $data[0]['expected_results'] = [
      [
        'collection' => 'language.af',
        'name' => 'user.settings',
        'data' => [
          'anonymous' => 'af - Anonymous',
        ],
      ],
      [
        'collection' => 'language.af',
        'name' => 'bar',
        'data' => FALSE,
      ],
    ];
    $data[0]['expected_count'] = NULL;
    $data[0]['configuration'] = [
      'names' => [
        'user.settings',
        'bar',
      ],
      'collections' => [
        'language.af',
      ],
    ];

    // Test with name and no collection in configuration.
    $data[1]['source_data'] = $data[0]['source_data'];
    $data[1]['expected_results'] = [
      [
        'collection' => 'language.af',
        'name' => 'bar',
        'data' => FALSE,
      ],
    ];
    $data[1]['expected_count'] = NULL;
    $data[1]['configuration'] = [
      'names' => [
        'bar',
      ],
    ];

    // Test with collection and no name in configuration.
    $data[2]['source_data'] = $data[0]['source_data'];
    $data[2]['expected_results'] = [
      [
        'collection' => 'language.de',
        'name' => 'user.settings',
        'data' => [
          'anonymous' => 'de - Anonymous',
        ],
      ],
    ];
    $data[2]['expected_count'] = NULL;
    $data[2]['configuration'] = [
      'collections' => [
        'language.de',
      ],
    ];

    return $data;
  }

}
