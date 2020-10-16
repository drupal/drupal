<?php

namespace Drupal\Tests\history\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests legacy history module functionality.
 *
 * @group history
 * @group legacy
 */
class HistoryLegacyTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'history'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('history', 'history');
  }

  /**
   * Tests for deprecated messages.
   */
  public function testLegacyRepository() {
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();
    $user = $this->createUser();
    \Drupal::currentUser()->setAccount($user);
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ]);
    $node->save();

    $this->expectDeprecation('history_write() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\history\HistoryRepositoryInterface::updateLastViewed() instead. See https://www.drupal.org/node/2197189');
    history_write($node->id(), $user);

    $this->expectDeprecation('history_read() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\history\HistoryRepositoryInterface::getLastViewed() instead. See https://www.drupal.org/node/2197189');
    $timestamp = history_read($node->id());
    $this->expectDeprecation('history_read_multiple() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\history\HistoryRepositoryInterface::getLastViewed() instead. See https://www.drupal.org/node/2197189');
    $timestamps = history_read_multiple([$node->id()]);
    $this->assertEquals($timestamps, [$node->id() => $timestamp]);
    $expected = \Drupal::service('history.repository')
      ->getLastViewed('node', [$node->id()]);
    $this->assertEquals($expected, $timestamps);
  }

}
