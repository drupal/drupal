<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path for string IDs in workspace_association.
 *
 * @group workspaces
 */
class WorkspaceAssociationStringIdsUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $checkEntityFieldDefinitionUpdates = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/workspaces.php',
    ];
  }

  /**
   * Tests the update path for string IDs in workspace_association.
   */
  public function testRunUpdates(): void {
    $schema = \Drupal::database()->schema();
    $find_primary_key_columns = new \ReflectionMethod(get_class($schema), 'findPrimaryKeyColumns');

    $this->assertFalse($schema->fieldExists('workspace_association', 'target_entity_id_string'));
    $primary_key_columns = ['workspace', 'target_entity_type_id', 'target_entity_id'];
    $this->assertEquals($primary_key_columns, $find_primary_key_columns->invoke($schema, 'workspace_association'));

    $this->runUpdates();

    $this->assertTrue($schema->fieldExists('workspace_association', 'target_entity_id_string'));
    $primary_key_columns = ['workspace', 'target_entity_type_id', 'target_entity_id', 'target_entity_id_string'];
    $this->assertEquals($primary_key_columns, $find_primary_key_columns->invoke($schema, 'workspace_association'));
  }

}
