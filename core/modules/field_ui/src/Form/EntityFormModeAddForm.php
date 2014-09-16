<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\EntityFormModeAddForm.
 */

namespace Drupal\field_ui\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityFormModeAddForm extends EntityDisplayModeAddForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->isFieldable() || !$definition->hasFormClasses()) {
      throw new NotFoundHttpException();
    }

    $this->entity->targetEntityType = $this->targetEntityTypeId;
  }

}
