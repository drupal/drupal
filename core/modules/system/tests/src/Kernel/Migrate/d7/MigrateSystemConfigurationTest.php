<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates various configuration objects owned by the System module.
 *
 * @group migrate_drupal_7
 */
class MigrateSystemConfigurationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'system'];

  /**
   * The expected configuration after migration.
   *
   * @var array
   */
  protected $expectedConfig = [
    'system.authorize' => [],
    'system.cron' => [
      'threshold' => [
        // Auto-run is not handled by the migration.
        // 'autorun' => 0,
        'requirements_warning' => 172800,
        'requirements_error' => 1209600,
      ],
      'logging' => TRUE,
    ],
    'system.date' => [
      'first_day' => 1,
      'country' => [
        'default' => 'US',
      ],
      'timezone' => [
        'default' => 'America/Chicago',
        'user' => [
          'configurable' => TRUE,
          // DRUPAL_USER_TIMEZONE_SELECT (D7 API)
          'default' => 2,
          'warn' => TRUE,
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
      'mailer_dsn' => [
        'scheme' => 'sendmail',
        'host' => 'default',
        'user' => NULL,
        'password' => NULL,
        'port' => NULL,
        'options' => [],
      ],
    ],
    'system.maintenance' => [
      // Langcode is not handled by the migration.
      'langcode' => 'en',
      'message' => 'This is a custom maintenance mode message.',
    ],
    'system.performance' => [
      'cache' => [
        'page' => [
          'max_age' => 300,
        ],
      ],
      'css' => [
        'preprocess' => TRUE,
        // Gzip is not handled by the migration.
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
        // Gzip is not handled by the migration.
        'gzip' => TRUE,
      ],
    ],
    'system.rss' => [
      'items' => [
        'view_mode' => 'fulltext',
      ],
    ],
    'system.site' => [
      // Neither langcode nor default_langcode are not handled by the migration.
      'langcode' => 'en',
      // UUID is not handled by the migration.
      'uuid' => '',
      'name' => 'The Site Name',
      'mail' => 'joseph@flattandsons.com',
      'slogan' => 'The Slogan',
      'page' => [
        '403' => '',
        '404' => '/node',
        'front' => '/node',
      ],
      'admin_compact_mode' => TRUE,
      'weight_select_max' => 100,
      'default_langcode' => 'en',
      'mail_notification' => NULL,
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

    // Delete 'site_403' in order to test the migration of a non-existing error
    // page link.
    $this->sourceDatabase->delete('variable')
      ->condition('name', 'site_403')
      ->execute();
    // Delete 'drupal_weight_select_max ' in order to test the migration when it
    // is not set.
    $this->sourceDatabase->delete('variable')
      ->condition('name', 'drupal_weight_select_max')
      ->execute();

    $this->config('system.site')
      ->set('weight_select_max', 5)
      ->save();

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
  public function testConfigurationMigration(): void {
    foreach ($this->expectedConfig as $config_id => $values) {
      if ($config_id == 'system.mail') {
        $actual = \Drupal::config($config_id)->getRawData();
      }
      else {
        $actual = \Drupal::config($config_id)->get();
      }
      unset($actual['_core']);
      $this->assertSame($values, $actual, $config_id . ' matches expected values.');
    }
    // The d7_system_authorize migration should not create the system.authorize
    // config.
    $this->assertTrue($this->config('system.authorize')->isNew());
  }

}
