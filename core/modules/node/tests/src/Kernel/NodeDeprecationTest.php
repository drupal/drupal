<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Form\NodeRevisionDeleteForm;

/**
 * Tests the deprecations in the node.module file.
 *
 * @group node
 * @group legacy
 */
class NodeDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'node'];

  /**
   * Tests the deprecation of node_revision_load.
   *
   * @see node_revision_load()
   */
  public function testNodeRevisionLoadDeprecation(): void {
    $this->installEntitySchema('node');
    $this->expectDeprecation('node_revision_load is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Entity\RevisionableStorageInterface::loadRevision instead. See https://www.drupal.org/node/3294237');
    node_revision_load(1);
  }

  /**
   * Tests the deprecation of node_revision_delete.
   *
   * @see node_revision_delete()
   */
  public function testNodeRevisionDeleteDeprecation(): void {
    $this->installEntitySchema('node');
    $this->expectDeprecation('node_revision_delete is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Entity\RevisionableStorageInterface::deleteRevision instead. See https://www.drupal.org/node/3294237');
    node_revision_delete(1);
  }

  /**
   * Tests the deprecation of node_type_update_nodes.
   *
   * @see node_type_update_nodes()
   */
  public function testNodeTypeUpdateNodesDeprecation(): void {
    $this->installEntitySchema('node');
    $this->expectDeprecation('node_type_update_nodes is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Entity\EntityStorageInterface::updateType instead. See https://www.drupal.org/node/3294237');
    node_type_update_nodes(1, 2);
  }

  /**
   * Tests the deprecation of NodeRevisionDeleteForm constructor.
   */
  public function testNodeRevisionDeleteFormConstructorDeprecation(): void {
    $this->expectDeprecation('Calling Drupal\node\Form\NodeRevisionDeleteForm::_construct() with the $connection argument is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3343754');
    new NodeRevisionDeleteForm(
      $this->createMock(EntityStorageInterface::class),
      $this->createMock(EntityStorageInterface::class),
      $this->createMock(Connection::class),
      $this->createMock(DateFormatterInterface::class),
    );
  }

}
