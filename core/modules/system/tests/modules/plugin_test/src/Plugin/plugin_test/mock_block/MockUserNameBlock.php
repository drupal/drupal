<?php

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Implementation of a user name block plugin used by Plugin API context test.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockUserNameBlock extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

  public function getTitle() {
    $user = $this->getContextValue('user');
    return $user->label();
  }

}
