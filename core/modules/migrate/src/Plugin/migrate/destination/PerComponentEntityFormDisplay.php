<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * This class imports one component of an entity form display.
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
