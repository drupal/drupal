<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides the delete form for entity display modes.
 *
 * @internal
 */
class EntityDisplayModeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity_type = $this->entity->getEntityType();
    return $this->t('Deleting a @entity-type will cause any output still requesting to use that @entity-type to use the default display settings.', ['@entity-type' => $entity_type->getLowercaseLabel()]);
  }

}
