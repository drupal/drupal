<?php

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Implementation of a String TypedData contextual block plugin used by Plugin
 * API context test.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class TypedDataStringBlock extends ContextAwarePluginBase {

  public function getTitle() {
    return $this->getContextValue('string');
  }

}
