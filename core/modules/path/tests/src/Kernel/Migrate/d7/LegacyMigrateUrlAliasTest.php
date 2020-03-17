<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\Traits\ExpectDeprecationTrait;

/**
 * Tests legacy URL alias migration.
 *
 * @group path
 * @group legacy
 */
class LegacyMigrateUrlAliasTest extends MigrateUrlAliasTest {
  use ExpectDeprecationTrait;
  /**
   * The legacy stub migration to use.
   *
   * @var array
   */
  protected $stubMigration = [
    'id' => 'd7_url_alias',
    'label' => 'URL aliases',
    'migration_tags' =>
      [
        0 => 'Drupal 7',
        1 => 'Content',
      ],
    'source' =>
      [
        'plugin' => 'd7_url_alias',
        'constants' =>
          [
            'slash' => '/',
          ],
      ],
    'process' =>
      [
        'source' =>
          [
            'plugin' => 'concat',
            'source' =>
              [
                0 => 'constants/slash',
                1 => 'source',
              ],
          ],
        'alias' =>
          [
            'plugin' => 'concat',
            'source' =>
              [
                0 => 'constants/slash',
                1 => 'alias',
              ],
          ],
        'langcode' => 'language',
        'node_translation' =>
          [
            0 =>
              [
                'plugin' => 'explode',
                'source' => 'source',
                'delimiter' => '/',
              ],
            1 =>
              [
                'plugin' => 'extract',
                'default' => 'INVALID_NID',
                'index' =>
                  [
                    0 => 1,
                  ],
              ],
            2 =>
              [
                'plugin' => 'migration_lookup',
                'migration' => 'd7_node_translation',
              ],
          ],
      ],
    'destination' =>
      [
        'plugin' => 'url_alias',
      ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    MigrateDrupal7TestBase::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installConfig('node');
    $this->installSchema('node', ['node_access']);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_node',
      'd7_node_translation',
    ]);
    $this->executeMigration(\Drupal::service('plugin.manager.migration')->createStubMigration($this->stubMigration));
    $this->addExpectedDeprecationMessage('UrlAlias is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the entity:path_alias destination instead. See https://www.drupal.org/node/3013865');
  }

}
