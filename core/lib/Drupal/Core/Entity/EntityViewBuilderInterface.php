<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityViewBuilderInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines an interface for entity view builders.
 *
 * @ingroup entity_api
 */
interface EntityViewBuilderInterface {

  /**
   * Builds the component fields and properties of a set of entities.
   *
   * @param &$build
   *   The renderable array representing the entity content.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities whose content is being built.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays
   *   The array of entity view displays holding the display options
   *   configured for the entity components, keyed by bundle name.
   * @param string $view_mode
   *   The view mode in which the entity is being viewed.
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode);

  /**
   * Builds the render array for the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view a Comment and passing a Node which is not the one the
   *   comment belongs to, or not passing one, and having the comment node not
   *   be available for loading.
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL);

  /**
   * Builds the render array for the provided entities.
   *
   * @param array $entities
   *   An array of entities implementing EntityInterface to view.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return
   *   A render array for the entities, indexed by the same keys as the
   *   entities array passed in $entities.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view Comments and passing a Node which is not the one the
   *   comments belongs to, or not passing one, and having the comments node not
   *   be available for loading.
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL);

  /**
   * Resets the entity render cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   (optional) If specified, the cache is reset for the given entities only.
   */
  public function resetCache(array $entities = NULL);

  /**
   * Builds a renderable array for the value of a single field in an entity.
   *
   * The resulting output is a fully themed field with label and multiple
   * values.
   *
   * This function can be used by third-party modules that need to output an
   * isolated field.
   * - Do not use inside node (or any other entity) templates; use
   *   render($content[FIELD_NAME]) instead.
   * - The FieldItemInterface::view() method can be used to output a single
   *   formatted field value, without label or wrapping field markup.
   *
   * The function takes care of invoking the prepare_view steps. It also
   * respects field access permissions.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   FieldItemList containing the values to be displayed.
   * @param string|array $display_options
   *  Can be either:
   *   - The name of a view mode. The field will be displayed according to the
   *     display settings specified for this view mode in the $field
   *     definition for the field in the entity's bundle. If no display settings
   *     are found for the view mode, the settings for the 'default' view mode
   *     will be used.
   *   - An array of display options. The following key/value pairs are allowed:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type. The default formatter will
   *       also be used if the requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Defaults to the
   *       formatter's default settings.
   *     - weight: (float) The weight to assign to the renderable element.
   *       Defaults to 0.
   *
   * @return array
   *   A renderable array for the field values.
   *
   * @see \Drupal\Core\Entity\EntityViewBuilderInterface::viewFieldItem()
   */
  public function viewField(FieldItemListInterface $items, $display_options = array());

  /**
   * Builds a renderable array for a single field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   FieldItem to be displayed.
   * @param string|array $display_options
   *   Can be either the name of a view mode, or an array of display settings.
   *   See EntityViewBuilderInterface::viewField() for more information.
   *
   * @return array
   *   A renderable array for the field item.
   *
   * @see \Drupal\Core\Entity\EntityViewBuilderInterface::viewField()
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = array());

  /**
   * The cache tag associated with this entity view builder.
   *
   * An entity view builder is instantiated on a per-entity type basis, so the
   * cache tags are also per-entity type.
   *
   * @return array
   *   An array of cache tags.
   */
  public function getCacheTags();

}
