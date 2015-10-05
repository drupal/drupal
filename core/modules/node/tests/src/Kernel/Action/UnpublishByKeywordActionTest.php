<?php

/**
 * @file
 * Contains \Drupal\Tests\node\Kernel\Action\UnpublishByKeywordActionTest.
 */

namespace Drupal\Tests\node\Kernel\Action;

use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Action;

/**
 * @group node
 */
class UnpublishByKeywordActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['action', 'node', 'system', 'user'];

  /**
   * Tests creating an action using the node_unpublish_by_keyword_action plugin.
   *
   * @see https://www.drupal.org/node/2578519
   */
  public function testUnpublishByKeywordAction() {
    Action::create([
      'id' => 'foo',
      'label' => 'Foobaz',
      'plugin' => 'node_unpublish_by_keyword_action',
    ])->save();
  }

}
