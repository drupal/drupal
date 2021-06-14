<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests node view access cacheability with node grants.
 *
 * @group node
 */
class NodeAccessCacheabilityWithNodeGrants extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'node_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests node view access cacheability with node grants.
   */
  public function testAccessCacheabilityWithNodeGrants() {
    NodeType::create(['type' => 'page'])->save();
    $this->createEntityReferenceField('node', 'page', 'ref', 'Ref', 'node');
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('ref', ['type' => 'entity_reference_label'])
      ->save();

    // Check that at least one module implements hook_node_grants() as this test
    // only tests this case.
    // @see \node_test_node_grants()
    $node_grants_implementations = \Drupal::moduleHandler()->getImplementations('node_grants');
    $this->assertNotEmpty($node_grants_implementations);

    // Create an unpublished node.
    $referenced = $this->createNode(['status' => FALSE]);
    // Create a node referencing $referenced.
    $node = $this->createNode(['ref' => $referenced]);

    // Check that the referenced entity link doesn't show on the host entity.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->linkNotExists($referenced->label());

    // Publish the referenced node.
    $referenced->setPublished()->save();

    // Check that the referenced entity link shows on the host entity.
    $this->getSession()->reload();
    $this->assertSession()->linkExists($referenced->label());
  }

}
