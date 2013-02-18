<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleStorageController.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Config\Config;

/**
 * Defines a controller class for image styles.
 */
class ImageStyleStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::importDelete().
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityInfo['config_prefix']);
    $entities = $this->load(array($id));
    $entity = $entities[$id];

    // @todo image_style_delete() supports the notion of a "replacement style"
    //   to be used by other modules instead of the deleted style. Essential!
    //   But that is impossible currently, since the config system only knows
    //   about deleted and added changes. Introduce an 'old_ID' key within
    //   config objects as a standard?
    return image_style_delete($entity);
  }

}
