<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditorInterface.
 */

namespace Drupal\edit\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines an interface for in-place editors plugins.
 */
interface InPlaceEditorInterface extends PluginInspectionInterface {

  /**
   * Checks whether this in-place editor is compatible with a given field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be in-place edited.
   *
   * @return bool
   *   TRUE if it is compatible, FALSE otherwise.
   */
  public function isCompatible(FieldItemListInterface $items);

  /**
   * Generates metadata that is needed specifically for this editor.
   *
   * Will only be called by \Drupal\edit\MetadataGeneratorInterface::generate()
   * when the passed in field instance & item values will use this editor.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be in-place edited.
   *
   * @return array
   *   A keyed array with metadata. Each key should be prefixed with the plugin
   *   ID of the editor.
   */
  public function getMetadata(FieldItemListInterface $items);

  /**
   * Returns the attachments for this editor.
   *
   * @return array
   *   An array of attachments, for use with #attached.
   *
   * @see drupal_process_attached()
   */
  public function getAttachments();

}
