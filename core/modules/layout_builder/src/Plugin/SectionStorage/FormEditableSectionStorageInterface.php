<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

/**
 * A method for getting the entity associated with the section storage.
 */
interface FormEditableSectionStorageInterface {

  /**
   * Get the entity being edited by the Layout Builder UI.
   *
   * @return mixed
   *   The entity being edited by the Layout Builder UI.
   */
  public function getContainingEntity();

}
