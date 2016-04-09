<?php

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests the variable source plugin.
 *
 * @group migrate_drupal
 */
class i18nVariableTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\i18nVariable';

  /**
   * Define bare minimum migration configuration.
   */
  protected $migrationConfiguration = [
    'id' => 'test',
    'highWaterProperty' => array('field' => 'test'),
    'source' => [
      'plugin' => 'i18n_variable',
      'variables' => [
        'site_slogan',
        'site_name',
      ],
    ],
  ];

  /**
   * Expected results from the source.
   */
  protected $expectedResults = [
    [
      'language' => 'fr',
      'site_slogan' => 'Migrate est génial',
      'site_name' => 'nom de site',
    ],
    [
      'language' => 'mi',
      'site_slogan' => 'Ko whakamataku heke',
      'site_name' => 'ingoa_pae',
    ]
  ];

  /**
   * Database contents for tests.
   */
  protected $databaseContents = [
    'i18n_variable' => [
      array('name' => 'site_slogan', 'language' => 'fr', 'value' => 's:19:"Migrate est génial";'),
      array('name' => 'site_name', 'language' => 'fr', 'value' => 's:11:"nom de site";'),
      array('name' => 'site_slogan', 'language' => 'mi', 'value' => 's:19:"Ko whakamataku heke";'),
      array('name' => 'site_name', 'language' => 'mi', 'value' => 's:9:"ingoa_pae";'),
    ],
  ];

}
