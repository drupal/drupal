<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests migrate_drupal_migrations_plugin_alter for d6 term node migrations.
 *
 * @group migrate_drupal
 */
class MigrationPluginAlterTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupDb();
  }

  /**
   * Tests migrate_drupal_migrations_plugin_alter without content_translation.
   *
   * @dataProvider providerMigrationPluginAlter
   */
  public function testMigrationPluginAlterNoTranslation($source, $expected) {
    $definitions = $source;
    migrate_drupal_migration_plugins_alter($definitions);
    // Ensure the results have an 'id' key.
    foreach ($definitions as $definition) {
      $this->assertArrayHasKey('id', $definition);
    }
    $this->assertSame($expected, $definitions);
  }

  /**
   * Data provider for testMigrationPluginAlter().
   */
  public function providerMigrationPluginAlter() {
    $tests = [];

    // Test without a d6_taxonomy_vocabulary definition.
    $tests[0]['source_data'] = [
      'test' => [
        'id' => 'test',
        'process' => [
          'nid' => 'nid',
        ],
      ],
    ];
    $tests[0]['expected_data'] = $tests[0]['source_data'];

    // Test with a d6_taxonomy_vocabulary definition.
    $tests[1]['source_data'] = [
      'd6_taxonomy_vocabulary' => [
        'id' => 'd6_taxonomy_vocabulary',
        'process' => [
          'vid' => [
            'plugin' => 'machine_name',
          ],
        ],
      ],
      'test' => [
        'id' => 'test',
        'process' => [
          'nid' => 'nid',
        ],
      ],
    ];
    $tests[1]['expected_data'] = $tests[1]['source_data'];

    // Test with a d6_taxonomy_vocabulary and term_node definitions.
    $tests[2] = $tests[1];
    $tests[2]['source_data']['d6_term_node:2'] = [
      'id' => 'd6_term_node:2',
      'process' => [
        'vid' => [
          'plugin' => 'machine_name',
        ],
      ],
    ];
    $tests[2]['source_data']['d6_term_node_revision:4'] = [
      'id' => 'd6_term_node_revision:4',
      'process' => [
        'vid' => [
          'plugin' => 'machine_name',
        ],
      ],
    ];

    $tests[2]['expected_data'] = $tests[2]['source_data'];
    $tests[2]['expected_data']['d6_term_node:2']['process']['taxonomy_forums'] = 'tid';
    $tests[2]['expected_data']['d6_term_node_revision:4']['process']['field_'] = 'tid';

    // Test with a d6_taxonomy_vocabulary and term_node_translation definition.
    $tests[3] = $tests[1];
    $tests[3]['source_data']['d6_term_node_translation:2'] = [
      'id' => 'd6_term_node_translation:2',
      'process' => [
        'vid' => [
          'plugin' => 'machine_name',
        ],
      ],
    ];

    $tests[3]['expected_data'] = $tests[3]['source_data'];
    return $tests;
  }

  /**
   * Tests migrate_drupal_migrations_plugin_alter.
   *
   * @dataProvider providerMigrationPluginAlterTranslation
   */
  public function testMigrationPluginAlterTranslation($source, $expected) {
    /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install(['content_translation']);
    $definitions = $source;
    migrate_drupal_migration_plugins_alter($definitions);
    // Ensure the results have an 'id' key.
    foreach ($definitions as $definition) {
      $this->assertArrayHasKey('id', $definition);
    }
    $this->assertSame($expected, $definitions);

  }

  /**
   * Data provider for providerMigrationPluginAlterTranslation().
   */
  public function providerMigrationPluginAlterTranslation() {
    $tests = [];

    // Test with a d6_taxonomy_vocabulary definition and
    // d6_term_node_translation definitions.
    $tests[0]['source_data'] = [
      'd6_taxonomy_vocabulary' => [
        'id' => 'd6_taxonomy_vocabulary',
        'process' => [
          'vid' => [
            'plugin' => 'machine_name',
          ],
        ],
      ],
      'test' => [
        'id' => 'test',
        'process' => [
          'nid' => 'nid',
        ],
      ],
      'd6_term_node_translation:2' => [
        'id' => 'd6_term_node_translation:2',
        'process' => [
          'vid' => [
            'plugin' => 'machine_name',
          ],
        ],
      ],
      'd6_term_node_translation:4' => [
        'id' => 'd6_term_node_translation:4',
        'process' => [
          'vid' => [
            'plugin' => 'machine_name',
          ],
        ],
      ],
    ];

    $tests[0]['expected_data'] = $tests[0]['source_data'];
    $tests[0]['expected_data']['d6_term_node_translation:2']['process']['taxonomy_forums'] = 'tid';
    $tests[0]['expected_data']['d6_term_node_translation:4']['process']['field_'] = 'tid';
    return $tests;
  }

  /**
   * Creates data in the source database.
   */
  protected function setupDb() {
    $this->sourceDatabase->schema()->createTable('system', [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'type' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'status' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
      ],
    ]);
    $this->sourceDatabase->insert('system')
      ->fields([
        'name',
        'type',
        'status',
      ])
      ->values([
        'name' => 'taxonomy',
        'type' => 'module',
        'status' => '1',
      ])
      ->execute();

    $this->sourceDatabase->schema()->createTable('variable', [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ],
        'value' => [
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'normal',
        ],
      ],
    ]);
    $this->sourceDatabase->insert('variable')
      ->fields([
        'name',
        'value',
      ])
      ->values([
        'name' => 'forum_nav_vocabulary',
        'value' => 's:1:"2";',
      ])
      ->execute();

    $this->sourceDatabase->schema()->createTable('vocabulary', [
      'fields' => [
        'vid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'size' => 'normal',
          'unsigned' => TRUE,
        ],
        'name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'description' => [
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'normal',
        ],
        'help' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'relations' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'hierarchy' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'multiple' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'required' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'tags' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'module' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'weight' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
      ],
      'primary key' => ['vid'],
    ]);

    $this->sourceDatabase->insert('vocabulary')
      ->fields([
        'vid',
        'name',
      ])
      ->values([
        'vid' => '4',
        'name' => 'Tags',
      ])
      ->values([
        'vid' => '2',
        'name' => 'Forums',
      ])
      ->execute();

    $this->sourceDatabase->schema()->createTable('vocabulary_node_types', [
      'fields' => [
        'vid' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'type' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ],
      ],
      'primary key' => [
        'vid',
        'type',
      ],
      'mysql_character_set' => 'utf8',
    ]);

    $this->sourceDatabase->insert('vocabulary_node_types')
      ->fields([
        'vid',
        'type',
      ])
      ->values([
        'vid' => '4',
        'type' => 'article',
      ])
      ->values([
        'vid' => '2',
        'type' => 'forum',
      ])
      ->execute();
  }

}
