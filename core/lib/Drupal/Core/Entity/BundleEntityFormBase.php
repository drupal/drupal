<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\BundleEntityFormBase.
 */

namespace Drupal\Core\Entity;

/**
 * Class BundleEntityFormBase is a base form for bundle config entities.
 */
class BundleEntityFormBase extends EntityForm {

  /**
   * Protects the bundle entity's ID property's form element against changes.
   *
   * This method is assumed to be called on a completely built entity form,
   * including a form element for the bundle config entity's ID property.
   *
   * @param array $form
   *   The completely built entity bundle form array.
   *
   * @return array
   *   The updated entity bundle form array.
   */
  protected function protectBundleIdElement(array $form) {
    $entity = $this->getEntity();
    $id_key = $entity->getEntityType()->getKey('id');
    assert('isset($form[$id_key])');
    $element = &$form[$id_key];

    // Make sure the element is not accidentally re-enabled if it has already
    // been disabled.
    if (empty($element['#disabled'])) {
      $element['#disabled'] = !$entity->isNew();
    }
    return $form;
  }

}
