<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginBase
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginBase as PluginBase;
use Drupal\Core\Plugin\Context\Context;

/**
 * Drupal specific class for plugins that use context.
 *
 * This class specifically overrides setContextValue to use the core version of
 * the Context class. This code is exactly the same as what is in Component
 * ContextAwarePluginBase but it is using a different Context class.
 */
abstract class ContextAwarePluginBase extends PluginBase {

  /**
   * Override of \Drupal\Component\Plugin\ContextAwarePluginBase::setContextValue().
   */
  public function setContextValue($key, $value) {
    $context_definition = $this->getContextDefinition($key);
    $this->context[$key] = new Context($context_definition);
    $this->context[$key]->setContextValue($value);

    return $this;
  }

}
