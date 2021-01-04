<?php

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates various configuration objects owned by the System module.
 *
 * @group migrate_drupal_7
 */
class MigrateSystemConfigurationTest extends MigrateDrupal7TestBase {

  protected static $modules = ['action', 'file', 'system'];

  protected $expectedConfig = [
    'system.authorize' => [
      'filetransfer_default' => 'ftp',
    ],
    'system.cron' => [
      'threshold' => [
        // autorun is not handled by the migration.
        // 'autorun' => 0,
        'requirements_warning' => 172800,
        'requirements_error' => 1209600,
      ],
      'logging' => 1,
    ],
    'system.date' => [
      'country' => [
        'default' => 'US',
      ],
      'first_day' => 1,
      'timezone' => [
        'default' => 'America/Chicago',
        'user' => [
          'configurable' => TRUE,
          'warn' => TRUE,
          // DRUPAL_USER_TIMEZONE_SELECT (D7 API)
          'default' => 2,
        ],
      ],
    ],
    'system.file' => [
      'allow_insecure_uploads' => TRUE,
      // default_scheme is not handled by the migration.
      'default_scheme' => 'public',
      // temporary_maximum_age is not handled by the migration.
      'temporary_maximum_age' => 21600,
    ],
    'system.image.gd' => [
      'jpeg_quality' => 80,
    ],
    'system.image' => [
      'toolkit' => 'gd',
    ],
    'system.logging' => [
      'error_level' => 'some',
    ],
    'system.mail' => [
      'interface' => [
        'default' => 'php_mail',
      ],
    ],
    'system.maintenance' => [
      'message' => 'This is a custom maintenance mode message.',
      // langcode is not handled by the migration.
      'langcode' => 'en',
    ],
    'system.performance' => [
      'cache' => [
        'page' => [
          'max_age' => 300,
        ],
      ],
      'css' => [
        'preprocess' => TRUE,
        // gzip is not handled by the migration.
        'gzip' => TRUE,
      ],
      // fast_404 is not handled by the migration.
      'fast_404' => [
        'enabled' => TRUE,
        'paths' => '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i',
        'exclude_paths' => '/\/(?:styles|imagecache)\//',
        'html' => '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
      ],
      'js' => [
        'preprocess' => FALSE,
        // gzip is not handled by the migration.
        'gzip' => TRUE,
      ],
      // stale_file_threshold is not handled by the migration.
      'stale_file_threshold' => 2592000,
    ],
    'system.rss' => [
      'items' => [
        'view_mode' => 'fulltext',
      ],
    ],
    'system.site' => [
      // uuid is not handled by the migration.
      'uuid' => '',
      'name' => 'The Site Name',
      'mail' => 'joseph@flattandsons.com',
      'slogan' => 'The Slogan',
      'page' => [
        '403' => '/node',
        '404' => '/node',
        'front' => '/node',
      ],
      'admin_compact_mode' => TRUE,
      'weight_select_max' => 40,
      // langcode and default_langcode are not handled by the migration.
      'langcode' => 'en',
      'default_langcode' => 'en',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The system_maintenance migration gets both the Drupal 6 and Drupal 7
    // site maintenance message. Add a row with the Drupal 6 version of the
    // maintenance message to confirm that the Drupal 7 variable is selected in
    // the migration.
    // See https://www.drupal.org/project/drupal/issues/3096676
    $this->sourceDatabase->insert('variable')
      ->fields([
        'name' => 'site_offline_message',
        'value' => 's:16:"Drupal 6 message";',
      ])
      ->execute();

    $migrations = [
      'd7_system_authorize',
      'd7_system_cron',
      'd7_system_date',
      'd7_system_file',
      'system_image_gd',
      'system_image',
      'system_logging',
      'd7_system_mail',
      'system_maintenance',
      'd7_system_performance',
      'system_rss',
      'system_site',
    ];
    $this->executeMigrations($migrations);
  }

  /**
   * Tests that all expected configuration gets migrated.
   */
  public function testConfigurationMigration() {
    foreach ($this->expectedConfig as $config_id => $values) {
      if ($config_id == 'system.mail') {
        $actual = \Drupal::config($config_id)->getRawData();
      }
      else {
        $actual = \Drupal::config($config_id)->get();
      }
      unset($actual['_core']);
      $this->assertSame($actual, $values, $config_id . ' matches expected values.');
    }
  }

}
