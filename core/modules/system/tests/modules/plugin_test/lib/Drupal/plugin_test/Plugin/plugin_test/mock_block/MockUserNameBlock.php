<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserNameBlock.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Implementation of a user name block plugin used by Plugin API context test.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockUserNameBlock extends ContextAwarePluginBase {

  public function getTitle() {
    $user = $this->getContextValue('user');
    return $user->label();
  }
}
