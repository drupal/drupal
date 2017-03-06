<?php

namespace Drupal\quickedit\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines a base in-place editor implementation.
 *
 * @see \Drupal\quickedit\Annotation\InPlaceEditor
 * @see \Drupal\quickedit\Plugin\InPlaceEditorInterface
 * @see \Drupal\quickedit\Plugin\InPlaceEditorManager
 * @see plugin_api
 */
abstract class InPlaceEditorBase extends PluginBase implements InPlaceEditorInterface {

  /**
   * {@inheritdoc}
   */
  public function getMetadata(FieldItemListInterface $items) {
    return [];
  }

}
