<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityFormModeAddForm.
 */

namespace Drupal\entity\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityFormModeAddForm extends EntityDisplayModeAddForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->entityType);
    if (!$definition['fieldable'] || !isset($definition['controllers']['form'])) {
      throw new NotFoundHttpException();
    }

    $this->entity->targetEntityType = $this->entityType;
  }

}
