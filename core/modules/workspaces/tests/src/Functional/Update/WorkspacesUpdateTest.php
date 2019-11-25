<?php

namespace Drupal\Tests\workspaces\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the upgrade path for the Workspaces module.
 *
 * @group workspaces
 * @group Update
 * @group legacy
 */
class WorkspacesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workspaces', 'workspace_update_test'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.6.0-workspaces_installed.php',
    ];
  }

  /**
   * Tests the move of workspace association data to a custom table.
   *
   * @see workspaces_update_8801()
   * @see workspaces_post_update_move_association_data()
   */
  public function testWorkspaceAssociationRemoval() {
    $database = \Drupal::database();

    // Check that we have two records in the 'workspace_association' base table
    // and three records in its revision table.
    $wa_records = $database->select('workspace_association')->countQuery()->execute()->fetchField();
    $this->assertEquals(2, $wa_records);
    $war_records = $database->select('workspace_association_revision')->countQuery()->execute()->fetchField();
    $this->assertEquals(3, $war_records);

    // Check that the node entity type does not have a 'workspace' field.
    $this->assertNull(\Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('workspace', 'node'));

    $this->runUpdates();

    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

    // Check that the 'workspace' field has been installed for an entity type
    // that was workspace-supported before Drupal 8.7.0.
    $this->assertTrue($entity_definition_update_manager->getFieldStorageDefinition('workspace', 'node'));

    // Check that the 'workspace' field has been installed for an entity type
    // which became workspace-supported as part of an entity schema update.
    $this->assertTrue($entity_definition_update_manager->getFieldStorageDefinition('workspace', 'taxonomy_term'));

    // Check that the 'workspace' field has been installed for an entity type
    // that has been added in an update function.
    $this->assertTrue($entity_definition_update_manager->getFieldStorageDefinition('workspace', 'path_alias'));

    // Check that the 'workspace' revision metadata field has been created only
    // in the revision table.
    $schema = $database->schema();
    $this->assertTrue($schema->fieldExists('node_revision', 'workspace'));
    $this->assertFalse($schema->fieldExists('node', 'workspace'));
    $this->assertFalse($schema->fieldExists('node_field_data', 'workspace'));
    $this->assertFalse($schema->fieldExists('node_field_revision', 'workspace'));

    // Check that the 'workspace_association' records have been migrated
    // properly.
    $wa_records = $database->select('workspace_association')->fields('workspace_association')->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $expected = [
      [
        'workspace' => 'stage',
        'target_entity_type_id' => 'node',
        'target_entity_id' => '1',
        'target_entity_revision_id' => '2',
      ],
      [
        'workspace' => 'dev',
        'target_entity_type_id' => 'node',
        'target_entity_id' => '8',
        'target_entity_revision_id' => '10',
      ],
    ];
    $this->assertEquals($expected, $wa_records);

    // Check that the 'workspace_association' revisions has been migrated
    // properly to the new 'workspace' revision metadata field.
    $revisions = \Drupal::entityTypeManager()->getStorage('node')->loadMultipleRevisions([2, 9, 10]);
    $this->assertEquals('stage', $revisions[2]->workspace->target_id);
    $this->assertEquals('dev', $revisions[9]->workspace->target_id);
    $this->assertEquals('dev', $revisions[10]->workspace->target_id);

    // Check that the 'workspace_association' entity type has been uninstalled.
    $this->assertNull($entity_definition_update_manager->getEntityType('workspace_association'));
    $this->assertNull($entity_definition_update_manager->getFieldStorageDefinition('id', 'workspace_association'));
    $this->assertNull(\Drupal::keyValue('entity.storage_schema.sql')->get('workspace_association.entity_schema_data'));

    // Check that the 'workspace_association_revision' table has been removed.
    $this->assertFalse($schema->tableExists('workspace_association_revision'));
  }

  /**
   * Tests the addition of the workspace 'parent' field.
   *
   * @see workspaces_update_8802()
   * @see workspaces_post_update_update_deploy_form_display()
   */
  public function testWorkspaceParentField() {
    $this->runUpdates();

    $this->assertNotEmpty(\Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('parent', 'workspace'));
    $stage = Workspace::load('stage');
    $this->assertTrue($stage->hasField('parent'));
    $this->assertTrue($stage->parent->isEmpty());

    // Check that the 'parent' field is hidden in the Deploy form display.
    $form_display = EntityFormDisplay::load('workspace.workspace.deploy');
    $this->assertNull($form_display->getComponent('parent'));
  }

  /**
   * Tests that there is no active workspace during database updates.
   */
  public function testActiveWorkspaceDuringUpdate() {
    /** @var \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspaces.manager');

    // Check that we have an active workspace before running the updates.
    $this->assertTrue($workspace_manager->hasActiveWorkspace());
    $this->assertEquals('test', $workspace_manager->getActiveWorkspace()->id());

    $this->runUpdates();

    // Check that we didn't have an active workspace while running the updates.
    // @see workspace_update_test_post_update_check_active_workspace()
    $this->assertFalse(\Drupal::state()->get('workspace_update_test.has_active_workspace'));

    // Check that we have an active workspace after running the updates.
    $this->assertTrue($workspace_manager->hasActiveWorkspace());
    $this->assertEquals('test', $workspace_manager->getActiveWorkspace()->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
