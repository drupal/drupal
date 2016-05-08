<?php

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Implementation of a complex context plugin used by Plugin API context tests.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockComplexContextBlock extends ContextAwarePluginBase {

  public function getTitle() {
    $user = $this->getContextValue('user');
    $node = $this->getContextValue('node');
    return $user->label() . ' -- ' . $node->label();
  }

}
