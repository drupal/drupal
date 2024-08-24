<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Implementation of a complex context plugin used by Plugin API context tests.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockComplexContextBlock extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

  public function getTitle() {
    $user = $this->getContextValue('user');
    $node = $this->getContextValue('node');
    return $user->label() . ' -- ' . $node->label();
  }

}
