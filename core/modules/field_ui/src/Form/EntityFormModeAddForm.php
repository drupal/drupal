<?php

namespace Drupal\field_ui\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 *
 * @internal
 */
class EntityFormModeAddForm extends EntityDisplayModeAddForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->get('field_ui_base_route') || !$definition->hasFormClasses()) {
      throw new NotFoundHttpException();
    }

    $this->entity->setTargetType($this->targetEntityTypeId);
  }

}
