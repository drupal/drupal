<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Implements a String TypedData contextual block plugin.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 * @see \Drupal\KernelTests\Core\Plugin\PluginTestBase
 */
class TypedDataStringBlock extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

  public function getTitle() {
    return $this->getContextValue('string');
  }

}
