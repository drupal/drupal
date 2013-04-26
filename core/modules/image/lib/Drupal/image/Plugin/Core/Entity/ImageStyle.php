<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Core\Entity\ImageStyle.
 */

namespace Drupal\image\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\image\ImageStyleInterface;

/**
 * Defines an image style configuration entity.
 *
 * @EntityType(
 *   id = "image_style",
 *   label = @Translation("Image style"),
 *   module = "image",
 *   controllers = {
 *     "storage" = "Drupal\image\ImageStyleStorageController"
 *   },
 *   uri_callback = "image_style_entity_uri",
 *   config_prefix = "image.style",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ImageStyle extends ConfigEntityBase implements ImageStyleInterface {

  /**
   * The name of the image style to use as replacement upon delete.
   *
   * @var string
   */
  protected $replacementID;

  /**
   * The name of the image style.
   *
   * @var string
   */
  public $name;

  /**
   * The image style label.
   *
   * @var string
   */
  public $label;

  /**
   * The array of image effects for this image style.
   *
   * @var string
   */
  public $effects;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

}
