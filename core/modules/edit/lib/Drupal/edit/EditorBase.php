<?php

/**
 * @file
 * Contains \Drupal\edit\EditorBase.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\edit\EditPluginInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a base editor implementation.
 */
abstract class EditorBase extends PluginBase implements EditPluginInterface {

  /**
   * Implements \Drupal\edit\EditPluginInterface::getMetadata().
   */
  function getMetadata(FieldInstance $instance, array $items) {
    return array();
  }

}
