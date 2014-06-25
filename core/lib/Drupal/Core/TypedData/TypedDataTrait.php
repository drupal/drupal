<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataTrait.
 */

namespace Drupal\Core\TypedData;

/**
 * Wrapper methods for classes that needs typed data manager object.
 */
trait TypedDataTrait {

  /**
   * The typed data manager used for creating the data types.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Sets the typed data manager.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The typed data manager.
   *
   * @return $this
   */
  public function setTypedDataManager(TypedDataManager $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
    return $this;
  }

  /**
   * Gets the typed data manager.
   *
   * @return \Drupal\Core\TypedData\TypedDataManager
   *   The typed data manager.
   */
  public function getTypedDataManager() {
    if (empty($this->typedDataManager)) {
      $this->typedDataManager = \Drupal::typedDataManager();
    }

    return $this->typedDataManager;
  }

}
