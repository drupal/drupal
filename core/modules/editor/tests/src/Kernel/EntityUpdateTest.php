<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

/**
 * Tests updating an entity.
 *
 * @group editor
 */
class EntityUpdateTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['editor', 'editor_test', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node']);

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    // Set editor_test module weight to be lower than editor module's weight so
    // that editor_test_entity_update() is called before editor_entity_update().
    $extension_config = \Drupal::configFactory()->get('core.extension');
    $editor_module_weight = $extension_config->get('module.editor');
    module_set_weight('editor_test', $editor_module_weight - 1);
  }

  /**
   * Tests updating an existing entity.
   *
   * @see editor_test_entity_update()
   */
  public function testEntityUpdate() {
    // Create a node.
    $node = Node::create([
      'type' => 'page',
      'title' => 'test',
    ]);
    $node->save();

    // Update the node.
    // What happens is the following:
    // 1. \Drupal\Core\Entity\EntityStorageBase::doPostSave() gets called.
    // 2. editor_test_entity_update() gets called.
    // 3. A resave of the updated entity gets triggered (second save call).
    // 4. \Drupal\Core\Entity\EntityStorageBase::doPostSave() gets called.
    // 5. editor_test_entity_update() gets called.
    // 6. editor_entity_update() gets called (caused by the second save call).
    // 7. editor_entity_update() gets called (caused by the first save call).
    $node->title->value = 'test updated';
    $node->save();
  }

}
