<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node search integration.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeSearchTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'search',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('search', ['search_index', 'search_dataset', 'search_total']);
    $this->installConfig(['search', 'system']);
    $type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $type->save();
  }

  /**
   * Tests that previous revisions of a node are not re-indexed.
   *
   * The idea is not to save URL aliases or execute certain procedures
   * if the node being processed is not the default revision.
   *
   * @see \Drupal\node\Hook\NodeSearchHooks::nodeUpdate()
   */
  public function testNodeReindexDefaultRevision(): void {
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Initial Title 1',
    ]);

    $initialRevisionId = $node->getRevisionId();

    $node->setTitle($this->randomMachineName());
    $node->setNewRevision();
    $node->save();

    // Set up the search configuration and the index.
    $plugin = \Drupal::service('plugin.manager.search')->createInstance('node_search');

    // Update the index.
    $plugin->updateIndex();

    $nodeStorage = \Drupal::service('entity_type.manager')->getStorage('node');
    $old_revision = $nodeStorage->loadRevision($initialRevisionId);
    $old_revision->save();

    // Check that updating a non-default revision did not trigger a reindex.
    $result = \Drupal::database()->select('search_dataset', 'sd')
      ->fields('sd', ['sid', 'type', 'reindex'])
      ->condition('reindex', 0, '>')
      ->execute()
      ->fetchAll();

    $this->assertCount(0, $result);
  }

}
