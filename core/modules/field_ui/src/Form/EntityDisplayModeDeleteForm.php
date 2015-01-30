<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\EntityDisplayModeDeleteForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides the delete form for entity display modes.
 */
class EntityDisplayModeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity_type = $this->entity->getEntityType();
    return t('Deleting a @entity-type will cause any output still requesting to use that @entity-type to use the default display settings.', array('@entity-type' => $entity_type->getLowercaseLabel()));
  }

}
