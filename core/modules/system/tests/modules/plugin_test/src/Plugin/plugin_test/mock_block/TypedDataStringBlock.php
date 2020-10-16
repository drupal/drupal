<?php

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Implementation of a String TypedData contextual block plugin used by Plugin
 * API context test.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class TypedDataStringBlock extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

  public function getTitle() {
    return $this->getContextValue('string');
  }

}
