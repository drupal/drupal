<?php

/**
 * @file
 * Contains \Drupal\edit\MetadataGenerator.
 */

namespace Drupal\edit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldInstance;

use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;


/**
 * Generates in-place editing metadata for an entity field.
 */
class MetadataGenerator implements MetadataGeneratorInterface {

   /**
   * An object that checks if a user has access to edit a given entity field.
   *
   * @var \Drupal\edit\Access\EditEntityFieldAccessCheckInterface
   */
  protected $accessChecker;

  /**
   * An object that determines which editor to attach to a given field.
   *
   * @var \Drupal\edit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * Constructs a new MetadataGenerator.
   *
   * @param \Drupal\edit\Access\EditEntityFieldAccessCheckInterface $access_checker
   *   An object that checks if a user has access to edit a given field.
   * @param \Drupal\edit\EditorSelectorInterface $editor_selector
   *   An object that determines which editor to attach to a given field.
   */
  public function __construct(EditEntityFieldAccessCheckInterface $access_checker, EditorSelectorInterface $editor_selector) {
    $this->accessChecker = $access_checker;
    $this->editorSelector = $editor_selector;
  }

  /**
   * Implements \Drupal\edit\MetadataGeneratorInterface::generate().
   */
  public function generate(EntityInterface $entity, FieldInstance $instance, $langcode, $view_mode) {
    $field_name = $instance['field_name'];

    // Early-return if user does not have access.
    $access = $this->accessChecker->accessEditEntityField($entity, $field_name);
    if (!$access) {
      return array('access' => FALSE);
    }

    $label = $instance['label'];
    $formatter_id = $instance->getFormatter($view_mode)->getPluginId();
    $items = $entity->get($field_name);
    $items = $items[$langcode];
    $editor = $this->editorSelector->getEditor($formatter_id, $instance, $items);
    $metadata = array(
      'label' => $label,
      'access' => TRUE,
      'editor' => $editor,
      'aria' => t('Entity @type @id, field @field', array('@type' => $entity->entityType(), '@id' => $entity->id(), '@field' => $label)),
    );
    // Additional metadata for WYSIWYG editor integration.
    if ($editor === 'direct-with-wysiwyg') {
      $format_id = $items[0]['format'];
      $metadata['format'] = $format_id;
      $metadata['formatHasTransformations'] = $this->textFormatHasTransformationFilters($format_id);
    }
    return $metadata;
  }

  /**
   * Returns whether the text format has transformation filters.
   */
  protected function textFormatHasTransformationFilters($format_id) {
    return (bool) count(array_intersect(array(FILTER_TYPE_TRANSFORM_REVERSIBLE, FILTER_TYPE_TRANSFORM_IRREVERSIBLE), filter_get_filter_types_by_format($format_id)));
  }

}
