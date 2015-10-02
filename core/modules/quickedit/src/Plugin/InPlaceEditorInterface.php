<?php

/**
 * @file
 * Contains \Drupal\quickedit\Plugin\InPlaceEditorInterface.
 */

namespace Drupal\quickedit\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines an interface for in-place editors plugins.
 *
 * @see \Drupal\quickedit\Annotation\InPlaceEditor
 * @see \Drupal\quickedit\Plugin\InPlaceEditorBase
 * @see \Drupal\quickedit\Plugin\InPlaceEditorManager
 * @see plugin_api
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
   * Will only be called by \Drupal\quickedit\MetadataGeneratorInterface::generate()
   * when the passed in field & item values will use this editor.
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
   * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments()
   */
  public function getAttachments();

}
