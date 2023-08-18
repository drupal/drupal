<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for field type category managers.
 */
interface FieldTypeCategoryManagerInterface extends PluginManagerInterface {

  /**
   * Fallback category for field types.
   */
  const FALLBACK_CATEGORY = 'general';

}
