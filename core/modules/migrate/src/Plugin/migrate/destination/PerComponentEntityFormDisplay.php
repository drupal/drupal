<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * This class imports one component of an entity form display.
 *
 * Destination properties expected in the imported row:
 * - entity_type: The entity type ID.
 * - bundle: The entity bundle.
 * - form_mode: The machine name of the form mode.
 * - field_name: The machine name of the field to be imported into the display.
 * - options: (optional) An array of options for displaying the field in this
 *   form mode.
 *
 * Examples:
 *
 * @code
 * source:
 *   constants:
 *     entity_type: node
 *     field_name: comment
 *     form_mode: default
 *     options:
 *       type: comment_default
 *       weight: 20
 * process:
 *   entity_type: 'constants/entity_type'
 *   field_name: 'constants/field_name'
 *   form_mode: 'constants/form_mode'
 *   options: 'constants/options'
 *   bundle: node_type
 * destination:
 *   plugin: component_entity_form_display
 * @endcode
 *
 * This will add a "comment" field on the "default" form mode of the "node"
 * entity type with options defined by the "options" constant.
 *
 * @MigrateDestination(
 *   id = "component_entity_form_display"
 * )
 */
class PerComponentEntityFormDisplay extends ComponentEntityDisplayBase {

  const MODE_NAME = 'form_mode';

  /**
   * {@inheritdoc}
   */
  protected function getEntity($entity_type, $bundle, $form_mode) {
    return entity_get_form_display($entity_type, $bundle, $form_mode);
  }

}
