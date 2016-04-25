<?php

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for "image style" configuration entities.
 */
class ImageStyleStorage extends ConfigEntityStorage implements ImageStyleStorageInterface {

  /**
   * Image style replacement memory storage.
   *
   * This value is not stored in the backend. It's used during the deletion of
   * an image style to save the replacement image style in the same request. The
   * value is used later, when resolving dependencies.
   *
   * @var string[]
   *
   * @see \Drupal\image\Form\ImageStyleDeleteForm::submitForm()
   */
  protected $replacement = [];

  /**
   * {@inheritdoc}
   */
  public function setReplacementId($name, $replacement) {
    $this->replacement[$name] = $replacement;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementId($name) {
    return isset($this->replacement[$name]) ? $this->replacement[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function clearReplacementId($name) {
    unset($this->replacement[$name]);
  }

}
