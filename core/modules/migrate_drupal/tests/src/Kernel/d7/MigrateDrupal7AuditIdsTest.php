<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate\Audit\AuditResult;
use Drupal\migrate\Audit\IdAuditor;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\DeprecatedModulesTestTrait;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;

/**
 * Tests the migration auditor for ID conflicts.
 *
 * @group migrate_drupal
 */
class MigrateDrupal7AuditIdsTest extends MigrateDrupal7TestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;
  use CreateTestContentEntitiesTrait;
  use ContentModerationTestTrait;
  use DeprecatedModulesTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Enable all modules.
    self::$modules = array_keys($this->coreModuleListDataProvider());
    self::$modules = $this->removeDeprecatedModules(self::$modules);
    parent::setUp();

    // Install required entity schemas.
    $this->installEntitySchemas();

    // Install required schemas.
    $this->installSchema('book', ['book']);
    $this->installSchema('dblog', ['watchdog']);
    $this->installSchema('forum', ['forum_index']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('search', ['search_dataset']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('tracker', ['tracker_node', 'tracker_user']);

    // Enable content moderation for nodes of type page.
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
    NodeType::create(['type' => 'page'])->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Tests multiple migrations to the same destination with no ID conflicts.
   */
  public function testMultipleMigrationWithoutIdConflicts() {
    // Create a node of type page.
    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $node->moderation_state->value = 'published';
    $node->save();

    // Insert data in the d7_node:page migration mapping table to simulate a
    // previously migrated node.
    $id_map = $this->getMigration('d7_node:page')->getIdMap();
    $table_name = $id_map->mapTableName();
    $id_map->getDatabase()->insert($table_name)
      ->fields([
        'source_ids_hash' => 1,
        'sourceid1' => 1,
        'destid1' => 1,
      ])
      ->execute();

    // Audit the IDs of the d7_node migrations for the page & article node type.
    // There should be no conflicts since the highest destination ID should be
    // equal to the highest migrated ID, as found in the aggregated mapping
    // tables of the two node migrations.
    $migrations = [
      $this->getMigration('d7_node:page'),
      $this->getMigration('d7_node:article'),
    ];

    $results = (new IdAuditor())->auditMultiple($migrations);
    /** @var \Drupal\migrate\Audit\AuditResult $result */
    foreach ($results as $result) {
      $this->assertInstanceOf(AuditResult::class, $result);
      $this->assertTrue($result->passed());
    }
  }

  /**
   * Tests all migrations with no ID conflicts.
   */
  public function testAllMigrationsWithNoIdConflicts() {
    $migrations = $this->container
      ->get('plugin.manager.migration')
      ->createInstancesByTag('Drupal 7');

    // Audit the IDs of all Drupal 7 migrations. There should be no conflicts
    // since no content has been created.
    $results = (new IdAuditor())->auditMultiple($migrations);
    /** @var \Drupal\migrate\Audit\AuditResult $result */
    foreach ($results as $result) {
      $this->assertInstanceOf(AuditResult::class, $result);
      $this->assertTrue($result->passed());
    }
  }

  /**
   * Tests all migrations with ID conflicts.
   */
  public function testAllMigrationsWithIdConflicts() {
    $migrations = $this->container
      ->get('plugin.manager.migration')
      ->createInstancesByTag('Drupal 7');

    // Create content.
    $this->createContent();

    // Audit the IDs of all Drupal 7 migrations. There should be conflicts since
    // content has been created.
    $conflicts = array_map(
      function (AuditResult $result) {
        return $result->passed() ? NULL : $result->getMigration()->getBaseId();
      },
      (new IdAuditor())->auditMultiple($migrations)
    );

    $expected = [
      'd7_aggregator_feed',
      'd7_aggregator_item',
      'd7_comment',
      'd7_custom_block',
      'd7_file',
      'd7_file_private',
      'd7_menu_links',
      'd7_node',
      'd7_node_revision',
      'd7_taxonomy_term',
      'd7_user',
      'node_translation_menu_links',
    ];
    $this->assertEmpty(array_diff(array_filter($conflicts), $expected));
  }

  /**
   * Tests draft revisions ID conflicts.
   */
  public function testDraftRevisionIdConflicts() {
    // Create a published node of type page.
    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $node->moderation_state->value = 'published';
    $node->save();

    // Create a draft revision.
    $node->moderation_state->value = 'draft';
    $node->setNewRevision(TRUE);
    $node->save();

    // Insert data in the d7_node_revision:page migration mapping table to
    // simulate a previously migrated node revision.
    $id_map = $this->getMigration('d7_node_revision:page')->getIdMap();
    $table_name = $id_map->mapTableName();
    $id_map->getDatabase()->insert($table_name)
      ->fields([
        'source_ids_hash' => 1,
        'sourceid1' => 1,
        'destid1' => 1,
      ])
      ->execute();

    // Audit the IDs of the d7_node_revision migration. There should be
    // conflicts since a draft revision has been created.
    /** @var \Drupal\migrate\Audit\AuditResult $result */
    $result = (new IdAuditor())->audit($this->getMigration('d7_node_revision:page'));
    $this->assertInstanceOf(AuditResult::class, $result);
    $this->assertFalse($result->passed());
  }

  /**
   * Tests ID conflicts for inaccessible nodes.
   */
  public function testNodeGrantsIdConflicts() {
    // Enable the node_test module to restrict access to page nodes.
    $this->enableModules(['node_test']);

    // Create a published node of type page.
    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $node->moderation_state->value = 'published';
    $node->save();

    // Audit the IDs of the d7_node migration. There should be conflicts
    // even though the new node is not accessible.
    /** @var \Drupal\migrate\Audit\AuditResult $result */
    $result = (new IdAuditor())->audit($this->getMigration('d7_node:page'));
    $this->assertInstanceOf(AuditResult::class, $result);
    $this->assertFalse($result->passed());
  }

}
