<?php

/**
 * @file
 * Definition of Drupal\user\UserRenderController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for users.
 */
class UserRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::getBuildDefaults().
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $return = parent::getBuildDefaults($entity, $view_mode, $langcode);

    // @todo rename "theme_user_profile" to "theme_user", 'account' to 'user'.
    $return['#theme'] = 'user_profile';
    $return['#account'] = $return['#user'];

    return $return;
  }
}
