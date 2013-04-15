<?php

/**
 * @file
 * Contains \Drupal\edit\EditorBase.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\edit\EditorInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Defines a base editor (Create.js PropertyEditor widget) implementation.
 */
abstract class EditorBase extends PluginBase implements EditorInterface {

  /**
   * Implements \Drupal\edit\EditorInterface::getMetadata().
   */
  function getMetadata(FieldInstance $instance, array $items) {
    return array();
  }

}
