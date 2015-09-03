<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\migrate\source\d7\UserTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 user source plugin.
 *
 * @group user
 */
class UserTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\d7\User';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_user',
    ],
  ];

  protected $expectedResults = [
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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['users'][] = [
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
    ];
    $this->databaseContents['users_roles'][] = [
      'uid' => 2,
      'rid' => 2,
    ];
    $this->databaseContents['role'][] = [
      'rid' => 2,
      'name' => 'authenticated user',
      'weight' => 0,
    ];
    $this->databaseContents['field_config_instance'] = [
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
    $this->databaseContents['field_data_field_file'] = [
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
    parent::setUp();
  }

}
