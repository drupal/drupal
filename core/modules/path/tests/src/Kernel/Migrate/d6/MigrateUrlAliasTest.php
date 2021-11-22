<?php

namespace Drupal\Tests\path\Kernel\Migrate\d6;

use Drupal\path_alias\PathAliasInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * URL alias migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUrlAliasTest extends MigrateDrupal6TestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'path',
    'path_alias',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);
    $this->migrateUsers(FALSE);
    $this->migrateFields();

    $this->executeMigrations([
      'language',
      'd6_node_settings',
      'd6_node',
      'd6_node_translation',
      'd6_url_alias',
    ]);
  }

  /**
   * Asserts that a path alias matches a set of conditions.
   *
   * @param int $pid
   *   The path alias ID.
   * @param array $conditions
   *   The path conditions.
   * @param \Drupal\path_alias\PathAliasInterface $path_alias
   *   The path alias.
   *
   * @internal
   */
  private function assertPath(int $pid, array $conditions, PathAliasInterface $path_alias): void {
    $this->assertSame($pid, (int) $path_alias->id());
    $this->assertSame($conditions['alias'], $path_alias->getAlias());
    $this->assertSame($conditions['langcode'], $path_alias->get('langcode')->value);
    $this->assertSame($conditions['path'], $path_alias->getPath());
  }

  /**
   * Tests the url alias migration.
   */
  public function testUrlAlias() {
    $id_map = $this->getMigration('d6_url_alias')->getIdMap();
    // Test that the field exists.
    $conditions = [
      'path' => '/node/1',
      'alias' => '/alias-one',
      'langcode' => 'af',
    ];
    $path_alias = $this->loadPathAliasByConditions($conditions);
    $this->assertPath(1, $conditions, $path_alias);
    $this->assertSame([['1']], $id_map->lookupDestinationIds([$path_alias->id()]), "Test IdMap");

    $conditions = [
      'path' => '/node/2',
      'alias' => '/alias-two',
      'langcode' => 'en',
    ];
    $path_alias = $this->loadPathAliasByConditions($conditions);
    $this->assertPath(2, $conditions, $path_alias);

    // Test that we can re-import using the UrlAlias destination.
    Database::getConnection('default', 'migrate')
      ->update('url_alias')
      ->fields(['dst' => 'new-url-alias'])
      ->condition('src', 'node/2')
      ->execute();

    \Drupal::database()
      ->update($id_map->mapTableName())
      ->fields(['source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE])
      ->execute();
    $migration = $this->getMigration('d6_url_alias');
    $this->executeMigration($migration);

    $path_alias = $this->loadPathAliasByConditions(['id' => $path_alias->id()]);
    $conditions['alias'] = '/new-url-alias';
    $this->assertPath(2, $conditions, $path_alias);

    $conditions = [
      'path' => '/node/3',
      'alias' => '/alias-three',
      'langcode' => 'und',
    ];
    $path_alias = $this->loadPathAliasByConditions($conditions);
    $this->assertPath(3, $conditions, $path_alias);

    $path_alias = $this->loadPathAliasByConditions(['alias' => '/source-noslash']);
    $conditions = [
      'path' => '/admin',
      'alias' => '/source-noslash',
      'langcode' => 'und',
    ];
    $this->assertPath(8, $conditions, $path_alias);

    // Tests the URL alias migration with translated nodes.
    // Alias for the 'The Real McCoy' node in English.
    $path_alias = $this->loadPathAliasByConditions(['alias' => '/the-real-mccoy']);
    $this->assertSame('/node/10', $path_alias->getPath());
    $this->assertSame('en', $path_alias->get('langcode')->value);

    // Alias for the 'The Real McCoy' French translation,
    // which should now point to node/10 instead of node/11.
    $path_alias = $this->loadPathAliasByConditions(['alias' => '/le-vrai-mccoy']);
    $this->assertSame('/node/10', $path_alias->getPath());
    $this->assertSame('fr', $path_alias->get('langcode')->value);

    // Alias for the 'Abantu zulu' node in Zulu.
    $path_alias = $this->loadPathAliasByConditions(['alias' => '/abantu-zulu']);
    $this->assertSame('/node/12', $path_alias->getPath());
    $this->assertSame('zu', $path_alias->get('langcode')->value);

    // Alias for the 'Abantu zulu' English translation,
    // which should now point to node/12 instead of node/13.
    $path_alias = $this->loadPathAliasByConditions(['alias' => '/the-zulu-people']);
    $this->assertSame('/node/12', $path_alias->getPath());
    $this->assertSame('en', $path_alias->get('langcode')->value);
  }

}
