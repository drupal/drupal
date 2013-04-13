<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceStorageController.
 */

namespace Drupal\field;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Controller class for field instances.
 *
 * Note: the class take no special care about importing instances after their
 * field in importCreate(), since this is guaranteed by the alphabetical order
 * (field.field.* entries are processed before field.instance.* entries).
 * @todo Revisit after http://drupal.org/node/1944368.
 */
class FieldInstanceStorageController extends ConfigStorageController {

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // If the field has been deleted in the same import, the instance will be
    // deleted by then, and there is nothing left to do. Just return TRUE so
    // that the file does not get written to active store.
    if (!$old_config->get()) {
      return TRUE;
    }
    return parent::importDelete($name, $new_config, $old_config);
  }

}
