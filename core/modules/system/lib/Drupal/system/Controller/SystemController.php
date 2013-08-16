<?php

/**
 * @file
 * Contains \Drupal\system\Controller\SystemController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Returns responses for System routes.
 */
class SystemController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Sets whether the admin menu is in compact mode or not.
   *
   * @param string $mode
   *   Valid values are 'on' and 'off'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  function compactPage($mode) {
    user_cookie_save(array('admin_compact_mode' => ($mode == 'on')));
    return new RedirectResponse(url('<front>', array('absolute' => TRUE)));
  }

}
