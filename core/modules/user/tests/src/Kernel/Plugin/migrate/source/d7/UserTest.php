<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d7_user source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d7\User
 * @group user
 */
class UserTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '11',
        'translatable' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '33',
        'field_id' => '11',
        'field_name' => 'field_file',
        'entity_type' => 'user',
        'bundle' => 'user',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_data_field_file'] = [
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => NULL,
        'language' => 'und',
        'delta' => 0,
        'field_file_fid' => 99,
        'field_file_display' => 1,
        'field_file_description' => 'None',
      ],
    ];
    $tests[0]['source_data']['role'] = [
      [
        'rid' => 2,
        'name' => 'authenticated user',
        'weight' => 0,
      ],
    ];
    $tests[0]['source_data']['users'] = [
      [
        'uid' => '2',
        'name' => 'Odo',
        'pass' => '$S$DVpvPItXvnsmF3giVEe7Jy2lG.SCoEs8uKwpHsyPvdeNAaNZYxZ8',
        'mail' => 'odo@local.host',
        'theme' => '',
        'signature' => '',
        'signature_format' => 'filtered_html',
        'created' => '1432750741',
        'access' => '0',
        'login' => '0',
        'status' => '1',
        'timezone' => 'America/Chicago',
        'language' => '',
        'picture' => '0',
        'init' => 'odo@local.host',
        'data' => 'a:1:{s:7:"contact";i:1;}',
      ],
    ];
    $tests[0]['source_data']['users_roles'] = [
      [
        'uid' => 2,
        'rid' => 2,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'uid' => '2',
        'name' => 'Odo',
        'pass' => '$S$DVpvPItXvnsmF3giVEe7Jy2lG.SCoEs8uKwpHsyPvdeNAaNZYxZ8',
        'mail' => 'odo@local.host',
        'signature' => '',
        'signature_format' => 'filtered_html',
        'created' => '1432750741',
        'access' => '0',
        'login' => '0',
        'status' => '1',
        'timezone' => 'America/Chicago',
        'language' => '',
        'picture' => '0',
        'init' => 'odo@local.host',
        'roles' => [2],
        'data' => [
          'contact' => 1,
        ],
        'field_file' => [
          [
            'fid' => 99,
            'display' => 1,
            'description' => 'None',
          ],
        ],
      ],
    ];

    return $tests;
  }

}
