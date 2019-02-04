<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests legacy user functionality.
 *
 * @group user
 * @group legacy
 */
class NodeLegacyTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();

    $this->installSchema('node', 'node_access');
  }

  /**
   * @expectedDeprecation node_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\node\Entity\Node::loadMultiple(). See https://www.drupal.org/node/2266845
   */
  public function testEntityLegacyCode() {
    $this->assertCount(0, node_load_multiple());
    Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ])->save();
    $this->assertCount(1, node_load_multiple());
    Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ])->save();
    $this->assertCount(2, node_load_multiple());
  }

}
