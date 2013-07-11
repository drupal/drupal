<?php

/**
 * @file
 * Contains \Drupal\edit\EditorBase.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\edit\EditPluginInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;

/**
 * Defines a base editor implementation.
 */
abstract class EditorBase extends PluginBase implements EditPluginInterface {

  /**
   * {@inheritdoc}
   */
  function getMetadata(FieldDefinitionInterface $field_definition, array $items) {
    return array();
  }

}
