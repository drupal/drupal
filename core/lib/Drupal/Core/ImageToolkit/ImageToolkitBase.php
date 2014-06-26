<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitBase.
 */

namespace Drupal\Core\ImageToolkit;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\PluginBase;

abstract class ImageToolkitBase extends PluginBase implements ImageToolkitInterface {

  /**
   * Image object this toolkit instance is tied to.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function setImage(ImageInterface $image) {
    if ($this->image) {
      throw new \BadMethodCallException(__METHOD__ . '() may only be called once.');
    }
    $this->image = $image;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    return array();
  }

}
