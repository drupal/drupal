<?php

namespace Drupal\Tests\history\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests History module's legacy code.
 *
 * @group history
 * @group legacy
 */
class HistoryLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['history', 'node', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('history', ['history']);
    $this->installSchema('system', ['sequences']);

  }

  /**
   * Tests history_attach_timestamp() deprecation.
   *
   * @expectedDeprecation history_attach_timestamp() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal\history\HistoryRenderCallback::lazyBuilder() instead. See https://www.drupal.org/node/2966725
   */
  public function testHistoryAttachTimestamp() {
    $node = Node::create([
      'title' => 'n1',
      'type' => 'default',
    ]);
    $node->save();

    $user1 = User::create([
      'name' => 'user1',
      'mail' => 'user1@example.com',
    ]);
    $user1->save();

    \Drupal::currentUser()->setAccount($user1);
    history_write(1);

    $render = history_attach_timestamp(1);
    $this->assertEquals(REQUEST_TIME, $render['#attached']['drupalSettings']['history']['lastReadTimestamps'][1]);
  }

}
