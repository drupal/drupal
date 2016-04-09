<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for an entity deletion form.
 *
 * @ingroup entity_api
 */
class EntityDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    // Only do dependency processing for configuration entities. Whilst it is
    // possible for a configuration entity to be dependent on a content entity,
    // these dependencies are soft and content delete permissions are often
    // given to more users. This method should not make assumptions that $entity
    // is a configuration entity in case we decide to remove the following
    // condition.
    if (!($entity instanceof ConfigEntityInterface)) {
      return $form;
    }
    $this->addDependencyListsToForm($form, $entity->getConfigDependencyKey(), $this->getConfigNamesToDelete($entity), $this->getConfigManager(), $this->entityManager);

    return $form;
  }

  /**
   * Gets the configuration manager.
   *
   * @return \Drupal\Core\Config\ConfigManager
   *   The configuration manager.
   */
  protected function getConfigManager() {
    return \Drupal::service('config.manager');
  }

  /**
   * Returns config names to delete for the deletion confirmation form.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity being deleted.
   *
   * @return string[]
   *   A list of configuration names that will be deleted by this form.
   */
  protected function getConfigNamesToDelete(ConfigEntityInterface $entity) {
    return [$entity->getConfigDependencyName()];
  }

}
