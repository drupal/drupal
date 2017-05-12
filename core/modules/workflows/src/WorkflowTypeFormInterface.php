<?php

namespace Drupal\workflows;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable workflow types.
 *
 * @see \Drupal\workflows\Plugin\WorkflowTypeFormBase
 * @see \Drupal\workflows\WorkflowTypeInterface
 * @see \Drupal\Core\Plugin\PluginFormInterface
 * @see plugin_api
 */
interface WorkflowTypeFormInterface extends PluginFormInterface, WorkflowTypeInterface {
}
