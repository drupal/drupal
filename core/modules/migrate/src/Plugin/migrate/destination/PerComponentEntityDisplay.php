<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * This class imports one component of an entity display.
 *
 * Destination properties expected in the imported row:
 * - entity_type: The entity type ID.
 * - bundle: The entity bundle.
 * - view_mode: The machine name of the view mode.
 * - field_name: The machine name of the field to be imported into the display.
 * - options: (optional) An array of options for displaying the field in this
 *   view mode.
 *
 * Examples:
 *
 * @code
 * source:
 *   constants:
 *     entity_type: user
 *     bundle: user
 *     view_mode: default
 *     field_name: user_picture
 *     type: image
 *     options:
 *       label: hidden
 *       settings:
 *         image_style: ''
 *         image_link: content
 * process:
 *   entity_type: 'constants/entity_type'
 *   bundle: 'constants/bundle'
 *   view_mode: 'constants/view_mode'
 *   field_name: 'constants/field_name'
 *   type: 'constants/type'
 *   options: 'constants/options'
 *   'options/type': '@type'
 * destination:
 *   plugin: component_entity_display
 * @endcode
 *
 * This will add the "user_picture" image field to the "default" view mode of
 * the "user" bundle of the "user" entity type with options as defined by the
 * "options" constant, for example the label will be hidden.
 *
 * @MigrateDestination(
 *   id = "component_entity_display"
 * )
 */
class PerComponentEntityDisplay extends ComponentEntityDisplayBase {

  const MODE_NAME = 'view_mode';

  /**
   * {@inheritdoc}
   */
  protected function getEntity($entity_type, $bundle, $view_mode) {
    return $this->entityDisplayRepository->getViewDisplay($entity_type, $bundle, $view_mode);
  }

}
