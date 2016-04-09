<?php

namespace Drupal\Core\Block;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of block plugins.
 */
interface BlockManagerInterface extends ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface {

}
