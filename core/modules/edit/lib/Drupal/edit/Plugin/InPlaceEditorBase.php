<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditorBase.
 */

namespace Drupal\edit\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines a base in-place editor implementation.
 */
abstract class InPlaceEditorBase extends PluginBase implements InPlaceEditorInterface {

  /**
   * {@inheritdoc}
   */
  function getMetadata(FieldItemListInterface $items) {
    return array();
  }

}
