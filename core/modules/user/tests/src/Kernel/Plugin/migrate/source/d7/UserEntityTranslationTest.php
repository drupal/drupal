<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 user entity translation source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d7\UserEntityTranslation
 *
 * @group user
 */
class UserEntityTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['entity_translation'] = [
      [
        'entity_type' => 'user',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'source' => '',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1531343498,
        'changed' => 1531343498,
      ],
      [
        'entity_type' => 'user',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
      ],
      [
        'entity_type' => 'user',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343456,
        'changed' => 1531343456,
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => 1,
        'field_name' => 'field_test',
        'type' => 'text',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => 1,
        'field_id' => 1,
        'field_name' => 'field_test',
        'entity_type' => 'user',
        'bundle' => 'user',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_data_field_test'] = [
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'delta' => 0,
        'field_test_value' => 'English field',
        'field_test_format' => NULL,
      ],
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'delta' => 0,
        'field_test_value' => 'French field',
        'field_test_format' => NULL,
      ],
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'delta' => 0,
        'field_test_value' => 'Spanish field',
        'field_test_format' => NULL,
      ],
    ];
    $tests[0]['source_data']['users'] = [
      [
        'uid' => 1,
        'name' => 'admin',
        'pass' => 'password123',
        'mail' => 'admin@example.com',
        'theme' => '',
        'signature' => '',
        'signature_format' => 'filtered_html',
        'created' => 1531343456,
        'access' => 1531343456,
        'login' => 1531343456,
        'status' => 1,
        'timezone' => 'America/New_York',
        'language' => 'fr',
        'picture' => 0,
        'init' => 'admin@example.com',
        'data' => 'a:0:{}',
      ],
    ];
    $tests[0]['source_data']['users_roles'] = [
      [
        'uid' => 1,
        'rid' => 3,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'user',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
        'field_test' => [
          [
            'value' => 'French field',
            'format' => NULL,
          ],
        ],
      ],
      [
        'entity_type' => 'user',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343456,
        'changed' => 1531343456,
        'field_test' => [
          [
            'value' => 'Spanish field',
            'format' => NULL,
          ],
        ],
      ],
    ];

    return $tests;
  }

}
