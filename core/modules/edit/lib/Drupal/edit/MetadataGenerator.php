<?php

/**
 * @file
 * Contains \Drupal\edit\MetadataGenerator.
 */

namespace Drupal\edit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;
use Drupal\field\FieldInstanceInterface;

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
   * The manager for editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * Constructs a new MetadataGenerator.
   *
   * @param \Drupal\edit\Access\EditEntityFieldAccessCheckInterface $access_checker
   *   An object that checks if a user has access to edit a given field.
   * @param \Drupal\edit\EditorSelectorInterface $editor_selector
   *   An object that determines which editor to attach to a given field.
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The manager for editor plugins.
   */
  public function __construct(EditEntityFieldAccessCheckInterface $access_checker, EditorSelectorInterface $editor_selector, PluginManagerInterface $editor_manager) {
    $this->accessChecker = $access_checker;
    $this->editorSelector = $editor_selector;
    $this->editorManager = $editor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateEntity(EntityInterface $entity, $langcode) {
    return array(
      'label' => $entity->label($langcode),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generateField(EntityInterface $entity, FieldDefinitionInterface $field_definition, $langcode, $view_mode) {
    $field_name = $field_definition->getFieldName();

    // Early-return if user does not have access.
    $access = $this->accessChecker->accessEditEntityField($entity, $field_name);
    if (!$access) {
      return array('access' => FALSE);
    }

    // Early-return if no editor is available.
    $formatter_id = entity_get_render_display($entity, $view_mode)->getRenderer($field_name)->getPluginId();
    $items = $entity->getTranslation($langcode)->get($field_name)->getValue();
    $editor_id = $this->editorSelector->getEditor($formatter_id, $field_definition, $items);
    if (!isset($editor_id)) {
      return array('access' => FALSE);
    }

    // Gather metadata, allow the editor to add additional metadata of its own.
    $label = $field_definition->getFieldLabel();
    $editor = $this->editorManager->createInstance($editor_id);
    $metadata = array(
      'label' => check_plain($label),
      'access' => TRUE,
      'editor' => $editor_id,
      'aria' => t('Entity @type @id, field @field', array('@type' => $entity->entityType(), '@id' => $entity->id(), '@field' => $label)),
    );
    $custom_metadata = $editor->getMetadata($field_definition, $items);
    if (count($custom_metadata)) {
      $metadata['custom'] = $custom_metadata;
    }

    return $metadata;
  }

}
