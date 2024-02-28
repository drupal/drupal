<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Tests for the MigrateController class.
 *
 * @group migrate_drupal_ui
 */
class MigrateMessageControllerTest extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_link_content',
    'message_test',
    'migrate_drupal_message_test',
    'migrate_drupal_ui',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Migration IDs.
   *
   * @var string[]
   */
  protected $migrationIds = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Migrations that access the source database in fields().
    $this->migrationIds = [
      'd6_menu',
      'd6_menu_links',
      'd6_profile_values',
      'd6_user',
      'd7_menu',
      'd7_menu_links',
      'd7_menu_test',
      'd7_user',
    ];

    $user = $this->createUser(['view migration messages']);
    $this->drupalLogin($user);
    $this->database = \Drupal::database();
  }

  /**
   * Tests the overview page for migrate messages.
   *
   * Tests the overview page with the following scenarios;
   * - No source database connection or message tables.
   * - No source database connection with message tables.
   * - A source database connection with message tables.
   */
  public function testOverview(): void {
    $session = $this->assertSession();

    // First, test with no source database or message tables.
    $this->drupalGet('/admin/reports/migration-messages');
    $session->titleEquals('Migration messages | Drupal');
    $session->pageTextContainsOnce('The upgrade process may log messages about steps that require user action or errors. This page allows you to view these messages');
    $session->pageTextContainsOnce('There are no migration message tables.');

    // Create map and message tables.
    $this->createMigrateTables($this->migrationIds);

    // Test overview with no source database connection and with message tables.
    $this->drupalGet('/admin/reports/migration-messages');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Failed to connect to your database server');
    $session->pageTextContains('database connection configured for source plugin variable.');
    foreach ($this->migrationIds as $migration_id) {
      $session->pageTextContains($migration_id);
    }

    // Create a source database connection.
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate_drupal_ui');
    $this->createSourceTables();

    // Now, test with a source database connection and with message tables.
    $this->drupalGet('/admin/reports/migration-messages');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('Failed to connect to your database server');
    foreach ($this->migrationIds as $migration_id) {
      $session->pageTextContains($migration_id);
    }
  }

  /**
   * Tests the detail pages for migrate messages.
   *
   * Tests the detail page with the following scenarios;
   * - No source database connection or message tables with a valid and an
   *   invalid migration.
   * - A source database connection with message tables with a valid and an
   *   invalid migration.
   * - A source database connection with message tables and a source plugin
   *   that does not have a description for a source ID in the values returned
   *   from fields().
   */
  public function testDetail(): void {
    $session = $this->assertSession();

    // Details page with invalid migration.
    $this->drupalGet('/admin/reports/migration-messages/invalid');
    $session->statusCodeEquals(404);
    $session->pageTextContains('Failed to connect to your database server');

    // Details page with valid migration.
    $this->drupalGet('/admin/reports/migration-messages/d7_menu');
    $session->statusCodeEquals(404);
    $session->pageTextNotContains('Failed to connect to your database server');

    // Create map and message tables.
    $this->createMigrateTables($this->migrationIds);

    $not_available_text = "When there is an error processing a row, the migration system saves the error message but not the source ID(s) of the row. That is why some messages in this table have 'Not available' in the source ID column(s).";

    // Test overview without a source database connection and with message
    // tables.
    $this->drupalGet('/admin/reports/migration-messages');
    $session->statusCodeEquals(200);
    foreach ($this->migrationIds as $migration_id) {
      $session->pageTextContains($migration_id);
    }

    // Test details page for each migration.
    foreach ($this->migrationIds as $migration_id) {
      $this->drupalGet("/admin/reports/migration-messages/$migration_id");
      $session->statusCodeEquals(200);
      $session->pageTextNotContains('No database connection configured for source plugin');
      $session->pageTextContains($migration_id);
      if ($migration_id == 'd7_menu') {
        // Confirm the descriptions from fields() are displayed.
        $session->pageTextContains('MENU NAME. PRIMARY KEY');
        $session->pageTextContains('Not available');
        $session->pageTextContains($not_available_text);
      }
    }

    // Create a source database connection.
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate_drupal_ui');
    $this->createSourceTables();

    // Now, test with a source database connect and with message tables.
    // Details page exists for each migration.
    foreach ($this->migrationIds as $migration_id) {
      $this->drupalGet("/admin/reports/migration-messages/$migration_id");
      $session->statusCodeEquals(200);
      $session->pageTextNotContains('No database connection configured for source plugin');
      $session->pageTextContains($migration_id);
      // Confirm the descriptions from fields() are displayed using d7_menu.
      if ($migration_id == 'd7_menu') {
        $session->pageTextContains('MENU NAME. PRIMARY KEY');
        $session->pageTextContains('Not available');
        $session->pageTextContains($not_available_text);
      }
      // Confirm the descriptions from fields() are displayed using
      // d7_menu_test, which has a source plugin that is missing the
      // 'menu_name' entry in fields().
      if ($migration_id == 'd7_menu_test') {
        $session->pageTextContains('MENU_NAME');
        $session->pageTextContains('Not available');
        $session->pageTextContains($not_available_text);
      }
    }

    // Details page for a migration without a map table.
    $this->database->schema()->dropTable('migrate_map_d7_menu');
    $this->drupalGet('/admin/reports/migration-messages/d7_menu');
    $session->statusCodeEquals(404);

    // Details page for a migration with a map table but no message table.
    $this->database->schema()->dropTable('migrate_message_d7_menu_links');
    $this->drupalGet('/admin/reports/migration-messages/d7_menu_links');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The message table is missing for this migration.');
  }

  /**
   * Creates map and message tables for testing.
   *
   * @see \Drupal\migrate\Plugin\migrate\id_map\Sql::ensureTables
   */
  protected function createMigrateTables(array $migration_ids): void {
    foreach ($migration_ids as $migration_id) {
      $map_table_name = "migrate_map_$migration_id";
      $message_table_name = "migrate_message_$migration_id";

      if (!$this->database->schema()->tableExists($map_table_name)) {
        $fields = [];
        $fields['source_ids_hash'] = [
          'type' => 'varchar',
          'length' => '64',
          'not null' => TRUE,
        ];
        $fields['sourceid1'] = [
          'type' => 'varchar',
          'length' => '255',
          'not null' => TRUE,
        ];
        $fields['destid1'] = [
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ];
        $fields['source_row_status'] = [
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => MigrateIdMapInterface::STATUS_IMPORTED,
        ];
        $fields['rollback_action'] = [
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => MigrateIdMapInterface::ROLLBACK_DELETE,
        ];
        $fields['last_imported'] = [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ];
        $fields['hash'] = [
          'type' => 'varchar',
          'length' => '64',
          'not null' => FALSE,
        ];
        $schema = [
          'description' => '',
          'fields' => $fields,
          'primary key' => ['source_ids_hash'],
        ];
        $this->database->schema()->createTable($map_table_name, $schema);

        $rows = [
          [
            'source_ids_hash' => '37c655d',
            'sourceid1' => 'navigation',
            'destid1' => 'tools',
            'source_row_status' => '0',
            'rollback_action' => '1',
            'last_imported' => '0',
            'hash' => '',
          ],
          [
            'source_ids_hash' => '3a34190',
            'sourceid1' => 'menu-fixedlang',
            'destid1' => 'menu-fixedlang',
            'source_row_status' => '0',
            'rollback_action' => '0',
            'last_imported' => '0',
            'hash' => '',
          ],
          [
            'source_ids_hash' => '3e51f67',
            'sourceid1' => 'management',
            'destid1' => 'admin',
            'source_row_status' => '0',
            'rollback_action' => '1',
            'last_imported' => '0',
            'hash' => '',
          ],
          [
            'source_ids_hash' => '94a5caa',
            'sourceid1' => 'user-menu',
            'destid1' => 'account',
            'source_row_status' => '0',
            'rollback_action' => '1',
            'last_imported' => '0',
            'hash' => '',
          ],
          [
            'source_ids_hash' => 'c0efbcca',
            'sourceid1' => 'main-menu',
            'destid1' => 'main',
            'source_row_status' => '0',
            'rollback_action' => '1',
            'last_imported' => '0',
            'hash' => '',
          ],
          [
            'source_ids_hash' => 'f64cb72f',
            'sourceid1' => 'menu-test-menu',
            'destid1' => 'menu-test-menu',
            'source_row_status' => '0',
            'rollback_action' => '0',
            'last_imported' => '0',
            'hash' => '',
          ],
        ];
        foreach ($rows as $row) {
          $this->database->insert($map_table_name)->fields($row)->execute();
        }
      }

      if (!$this->database->schema()->tableExists($message_table_name)) {
        $fields = [];
        $fields['msgid'] = [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ];
        $fields['source_ids_hash'] = [
          'type' => 'varchar',
          'length' => '64',
          'not null' => TRUE,
        ];
        $fields['level'] = [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 1,
        ];
        $fields['message'] = [
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ];
        $schema = [
          'description' => '',
          'fields' => $fields,
          'primary key' => ['msgid'],
        ];
        $this->database->schema()->createTable($message_table_name, $schema);

        $rows = [
          [
            'msgid' => '1',
            'source_ids_hash' => '28cfb3d1',
            'level' => '1',
            'message' => 'Config entities can not be stubbed.',
          ],
          [
            'msgid' => '2',
            'source_ids_hash' => '28cfb3d1',
            'level' => '1',
            'message' => 'Config entities can not be stubbed.',
          ],
          [
            'msgid' => '3',
            'source_ids_hash' => '05914d93',
            'level' => '1',
            'message' => 'Config entities can not be stubbed.',
          ],
          [
            'msgid' => '4',
            'source_ids_hash' => '05914d93',
            'level' => '1',
            'message' => 'Config entities can not be stubbed.',
          ],
        ];
        foreach ($rows as $row) {
          $this->database->insert($message_table_name)->fields($row)->execute();
        }
      }
    }

  }

  /**
   * Create source tables.
   */
  protected function createSourceTables(): void {
    $this->sourceDatabase->schema()->createTable('menu_custom', [
      'fields' => [
        'menu_name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ],
        'title' => [
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
      ],
      'primary key' => [
        'menu_name',
      ],
      'mysql_character_set' => 'utf8',
    ]);

    $this->sourceDatabase->schema()->createTable('profile_values', [
      'fields' => [
        'fid' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'uid' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
          'unsigned' => TRUE,
        ],
        'value' => [
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'normal',
        ],
      ],
      'primary key' => [
        'fid',
        'uid',
      ],
      'mysql_character_set' => 'utf8',
    ]);

    $this->sourceDatabase->schema()->createTable('profile_fields', [
      'fields' => [
        'fid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'size' => 'normal',
        ],
        'title' => [
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ],
        'name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ],
        'explanation' => [
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'normal',
        ],
        'category' => [
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ],
        'page' => [
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ],
        'type' => [
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '128',
        ],
        'weight' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'required' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'register' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'visibility' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'autocomplete' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'options' => [
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'normal',
        ],
      ],
      'primary key' => [
        'fid',
      ],
      'mysql_character_set' => 'utf8',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental(): array {
    return [];
  }

}
