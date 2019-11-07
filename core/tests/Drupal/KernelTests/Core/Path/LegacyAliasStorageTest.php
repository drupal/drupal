<?php

namespace Drupal\KernelTests\Core\Path;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Path\AliasStorage
 * @group path
 * @group legacy
 */
class LegacyAliasStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'path_alias'];

  /**
   * @var \Drupal\Core\Path\AliasStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('path_alias');
    $this->storage = $this->container->get('path.alias_storage');
  }

  /**
   * @covers ::load
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testLoad() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');

    $expected = [
      'pid' => 1,
      'alias' => '/test-alias-Case',
      'source' => '/test-source-Case',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];

    $this->assertEquals($expected, $this->storage->load(['alias' => '/test-alias-Case']));
    $this->assertEquals($expected, $this->storage->load(['alias' => '/test-alias-case']));
    $this->assertEquals($expected, $this->storage->load(['source' => '/test-source-Case']));
    $this->assertEquals($expected, $this->storage->load(['source' => '/test-source-case']));
  }

  /**
   * @covers ::load
   * @covers ::save
   * @covers ::delete
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testCRUD() {
    $entity_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $aliases = $this->sampleUrlAliases();

    // Create a few aliases
    foreach ($aliases as $idx => $alias) {
      $this->storage->save($alias['source'], $alias['alias'], $alias['langcode']);

      $result = $entity_storage->getQuery()
        ->condition('path', $alias['source'])
        ->condition('alias', $alias['alias'])
        ->condition('langcode', $alias['langcode'])
        ->execute();

      $this->assertCount(1, $result, "Created an entry for {$alias['alias']}.");

      // Cache the pid for further tests.
      $aliases[$idx]['pid'] = reset($result);
    }

    // Load a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $loadedAlias = $this->storage->load(['pid' => $pid]);
      $this->assertEquals($alias, $loadedAlias, "Loaded the expected path with pid $pid.");
    }

    // Load alias by source path.
    $loadedAlias = $this->storage->load(['source' => '/node/1']);
    $this->assertEquals('/alias_for_node_1_und', $loadedAlias['alias'], 'The last created alias loaded by default.');

    // Update a few aliases
    foreach ($aliases as $alias) {
      $fields = $this->storage->save($alias['source'], $alias['alias'] . '_updated', $alias['langcode'], $alias['pid']);

      $this->assertEquals($alias['alias'], $fields['original']['alias']);

      $result = $entity_storage->getQuery()
        ->condition('path', $alias['source'])
        ->condition('alias', $alias['alias'] . '_updated')
        ->condition('langcode', $alias['langcode'])
        ->execute();
      $pid = reset($result);

      $this->assertEquals($alias['pid'], $pid, "Updated entry for pid $pid.");
    }

    // Delete a few aliases
    foreach ($aliases as $alias) {
      $pid = $alias['pid'];
      $this->storage->delete(['pid' => $pid]);

      $result = $entity_storage->getQuery()->condition('id', $pid)->execute();

      $this->assertCount(0, $result, "Deleted entry with pid $pid.");
    }
  }

  /**
   * Returns an array of URL aliases for testing.
   *
   * @return array of URL alias definitions.
   */
  protected function sampleUrlAliases() {
    return [
      [
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_en',
        'langcode' => 'en',
      ],
      [
        'source' => '/node/2',
        'alias' => '/alias_for_node_2_en',
        'langcode' => 'en',
      ],
      [
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_fr',
        'langcode' => 'fr',
      ],
      [
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_und',
        'langcode' => 'und',
      ],
    ];
  }

  /**
   * @covers ::preloadPathAlias
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testPreLoadPathAlias() {
    $this->storage->save('/test-source-Case', '/test-alias');

    $this->assertEquals(['/test-source-Case' => '/test-alias'], $this->storage->preloadPathAlias(['/test-source-Case'], LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::lookupPathAlias
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testLookupPathAlias() {
    $this->storage->save('/test-source-Case', '/test-alias');

    $this->assertEquals('/test-alias', $this->storage->lookupPathAlias('/test-source-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertEquals('/test-alias', $this->storage->lookupPathAlias('/test-source-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::lookupPathSource
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testLookupPathSource() {
    $this->storage->save('/test-source', '/test-alias-Case');

    $this->assertEquals('/test-source', $this->storage->lookupPathSource('/test-alias-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertEquals('/test-source', $this->storage->lookupPathSource('/test-alias-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::aliasExists
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testAliasExists() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');

    $this->assertTrue($this->storage->aliasExists('/test-alias-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertTrue($this->storage->aliasExists('/test-alias-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::languageAliasExists
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testLanguageAliasExists() {
    $this->assertFalse($this->storage->languageAliasExists());

    $this->storage->save('/test-source-Case', '/test-alias-Case', 'en');
    $this->assertTrue($this->storage->languageAliasExists());
  }

  /**
   * @covers ::getAliasesForAdminListing
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testGetAliasesForAdminListing() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');
    $this->storage->save('/another-test', '/another-test-alias');

    $expected_alias_1 = new \stdClass();
    $expected_alias_1->pid = '2';
    $expected_alias_1->source = '/another-test';
    $expected_alias_1->alias = '/another-test-alias';
    $expected_alias_1->langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

    $expected_alias_2 = new \stdClass();
    $expected_alias_2->pid = '1';
    $expected_alias_2->source = '/test-source-Case';
    $expected_alias_2->alias = '/test-alias-Case';
    $expected_alias_2->langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

    $header = [['field' => 'alias', 'sort' => 'asc']];
    $this->assertEquals([$expected_alias_1, $expected_alias_2], $this->storage->getAliasesForAdminListing($header));
  }

  /**
   * @covers ::pathHasMatchingAlias
   * @expectedDeprecation \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.
   */
  public function testPathHasMatchingAlias() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');

    $this->assertTrue($this->storage->pathHasMatchingAlias('/test'));
    $this->assertFalse($this->storage->pathHasMatchingAlias('/another'));
  }

}
