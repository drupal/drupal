<?php

namespace Drupal\Tests\tracker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests deprecated tracker methods.
 *
 * @group legacy
 * @group tracker
 */
class TrackerDeprecationTest extends KernelTestBase {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * User for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;


  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'tracker', 'comment', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('tracker', 'tracker_node');
    $this->installSchema('tracker', 'tracker_user');
    $this->installSchema('comment', 'comment_entity_statistics');

    // Create a test user.
    $this->installEntitySchema('user');
    $this->user = User::create([
      'name' => 'foo',
    ]);
    $this->user->save();

    // Create a test node.
    $this->installEntitySchema('node');
    $this->node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'uid' => $this->user->id(),
    ]);
    $this->node->save();
  }

  /**
   * Test the deprecation of _tracker_add().
   */
  public function testDeprecatedTrackerAdd() {
    $this->expectDeprecation('_tracker_add() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\tracker\TrackerStorageInterface::add() instead. See https://www.drupal.org/node/3209781');
    $this->assertNull(_tracker_add($this->node->id(), $this->user->id(), 0));
  }

  /**
   * Test the deprecation of _tracker_calculate_changed().
   */
  public function testDeprecatedTrackerCalculateChanged() {
    $this->expectDeprecation('_tracker_calculate_changed() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\tracker\TrackerStorageInterface::calculateChanged() instead. See https://www.drupal.org/node/3209781');
    $this->assertGreaterThan(0, _tracker_calculate_changed($this->node));
  }

  /**
   * Test the deprecation of _tracker_remove().
   */
  public function testDeprecatedTrackerRemove() {
    $this->expectDeprecation('_tracker_remove() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\tracker\TrackerStorageInterface::remove() instead. See https://www.drupal.org/node/3209781');
    $this->assertNull(_tracker_remove($this->node->id(), $this->user->id()));
  }

}
