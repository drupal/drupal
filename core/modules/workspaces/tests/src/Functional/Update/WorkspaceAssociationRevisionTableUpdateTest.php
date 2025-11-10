<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path for the workspace_association_revision table.
 */
#[Group('workspaces')]
#[RunTestsInSeparateProcesses]
class WorkspaceAssociationRevisionTableUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/workspaces-10.3.0.php',
    ];
  }

  /**
   * Tests that the workspace_association_revision table is created and populated.
   */
  public function testWorkspaceAssociationRevisionTableUpdate(): void {
    $connection = \Drupal::database();
    $schema = $connection->schema();
    $entity_type_manager = \Drupal::entityTypeManager();

    // Ensure the table doesn't exist before the update.
    $this->assertFalse($schema->tableExists('workspace_association_revision'));

    // Get workspace associations to test with.
    $workspace_associations = $connection->select('workspace_association', 'wa')
      ->fields('wa')
      ->execute()
      ->fetchAll();

    // Verify we have the correct test data in the workspace_association table:
    // 4 node revisions and 4 taxonomy term revisions
    $this->assertCount(8, $workspace_associations, 'Test data exists in workspace_association table.');

    // Store expected results using the old getAssociatedRevisions logic.
    $expected_revisions = [];
    $expected_initial_revisions = [];

    foreach ($workspace_associations as $association) {
      $workspace_id = $association->workspace;
      $entity_type_id = $association->target_entity_type_id;

      if (!isset($expected_revisions[$workspace_id][$entity_type_id])) {
        // Replicate old getAssociatedRevisions logic.
        $expected_revisions[$workspace_id][$entity_type_id] = $this->getOldAssociatedRevisions(
          $connection, $entity_type_manager, $workspace_id, $entity_type_id
        );

        // Replicate old getAssociatedInitialRevisions logic.
        $expected_initial_revisions[$workspace_id][$entity_type_id] = $this->getOldAssociatedInitialRevisions(
          $connection, $entity_type_manager, $workspace_id, $entity_type_id
        );
      }
    }

    // Check that we have the proper expectations based on the test data from
    // update fixture.
    $this->assertCount(10, $expected_revisions['summer']['node']);
    $this->assertCount(4, $expected_revisions['summer']['taxonomy_term']);
    $this->assertCount(10, $expected_revisions['winter']['node']);
    $this->assertCount(4, $expected_revisions['winter']['taxonomy_term']);

    // Run the update.
    $this->runUpdates();

    // Verify the table was created.
    $this->assertTrue($schema->tableExists('workspace_association_revision'));

    // Now test with the updated methods from the workspace tracker service.
    /** @var \Drupal\workspaces\WorkspaceTrackerInterface $workspace_tracker */
    $workspace_tracker = \Drupal::service('workspaces.tracker');

    // Compare results for each workspace/entity type combination.
    foreach ($expected_revisions as $workspace_id => $entity_types) {
      foreach ($entity_types as $entity_type_id => $expected_result) {
        $actual_result = $workspace_tracker->getAllTrackedRevisions($workspace_id, $entity_type_id);
        $this->assertEquals($expected_result, $actual_result, sprintf(
          'Associated revisions match for workspace %s, entity type %s',
          $workspace_id, $entity_type_id
        ));
      }
    }

    // Compare initial revisions.
    foreach ($expected_initial_revisions as $workspace_id => $entity_types) {
      foreach ($entity_types as $entity_type_id => $expected_result) {
        $actual_result = $workspace_tracker->getTrackedInitialRevisions($workspace_id, $entity_type_id);
        $this->assertEquals($expected_result, $actual_result, sprintf(
          'Associated initial revisions match for workspace %s, entity type %s',
          $workspace_id, $entity_type_id
        ));
      }
    }
  }

  /**
   * Replicates the old getAssociatedRevisions logic from 11.x.
   */
  private function getOldAssociatedRevisions($connection, $entity_type_manager, $workspace_id, $entity_type_id, $entity_ids = NULL): array {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type_id);

    $entity_type = $storage->getEntityType();
    $table_mapping = $storage->getTableMapping();

    $id_field = $table_mapping->getColumnNames($entity_type->getKey('id'))['value'];
    $revision_id_field = $table_mapping->getColumnNames($entity_type->getKey('revision'))['value'];

    $query = $connection->select($entity_type->getRevisionTable(), 'revision');
    $query->leftJoin($entity_type->getBaseTable(), 'base', "revision.$id_field = base.$id_field");

    $query
      ->fields('revision', [$revision_id_field, $id_field])
      ->condition("revision.workspace", $workspace_id, '=')
      ->where("revision.$revision_id_field >= base.$revision_id_field")
      ->orderBy("revision.$revision_id_field", 'ASC');

    // Restrict the result to a set of entity ID's if provided.
    if ($entity_ids) {
      $query->condition("revision.$id_field", $entity_ids, 'IN');
    }

    $result = $query->execute()->fetchAllKeyed();

    return $result;
  }

  /**
   * Replicates the old getAssociatedInitialRevisions logic from 11.x.
   */
  private function getOldAssociatedInitialRevisions($connection, $entity_type_manager, $workspace_id, $entity_type_id, array $entity_ids = []): array {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type_id);

    $entity_type = $storage->getEntityType();
    $table_mapping = $storage->getTableMapping();

    $id_field = $table_mapping->getColumnNames($entity_type->getKey('id'))['value'];
    $revision_id_field = $table_mapping->getColumnNames($entity_type->getKey('revision'))['value'];

    $query = $connection->select($entity_type->getBaseTable(), 'base');
    $query->leftJoin($entity_type->getRevisionTable(), 'revision', "base.$revision_id_field = revision.$revision_id_field");

    $query
      ->fields('base', [$revision_id_field, $id_field])
      ->condition("revision.workspace", $workspace_id, '=')
      ->orderBy("base.$revision_id_field", 'ASC');

    // Restrict the result to a set of entity ID's if provided.
    if ($entity_ids) {
      $query->condition("base.$id_field", $entity_ids, 'IN');
    }

    $result = $query->execute()->fetchAllKeyed();

    return $result;
  }

}
