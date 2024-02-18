<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Defines a generic configuration element.
 */
abstract class Element extends TypedData {

  /**
   * The configuration value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Gets the typed configuration manager.
   *
   * Overrides \Drupal\Core\TypedData\TypedDataTrait::getTypedDataManager() to
   * ensure the typed configuration manager is returned.
   *
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   *   The typed configuration manager.
   */
  public function getTypedDataManager() {
    if (empty($this->typedDataManager)) {
      $this->setTypedDataManager(\Drupal::service('config.typed'));
    }

    return $this->typedDataManager;
  }

  /**
   * Sets the typed config manager.
   *
   * Overrides \Drupal\Core\TypedData\TypedDataTrait::setTypedDataManager() to
   * ensure that only typed configuration manager can be used.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed config manager. This must be an instance of
   *   \Drupal\Core\Config\TypedConfigManagerInterface. If it is not, then this
   *   method will error when assertions are enabled. We can not narrow the
   *   type hint as this will cause PHP errors.
   *
   * @return $this
   */
  public function setTypedDataManager(TypedDataManagerInterface $typed_data_manager) {
    assert($typed_data_manager instanceof TypedConfigManagerInterface, '$typed_data_manager should be an instance of \Drupal\Core\Config\TypedConfigManagerInterface.');
    $this->typedDataManager = $typed_data_manager;
    return $this;
  }

}
