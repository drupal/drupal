<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockController.
 */

namespace Drupal\block\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for admin block routes.
 */
class BlockController extends ControllerBase {

  /**
   * Returns a block theme demo page.
   *
   * @param string $theme
   *   The name of the theme.
   *
   * @return array
   *   A render array containing the CSS and title for the block region demo.
   */
  public function demo($theme) {
    $themes = list_themes();
    return array(
      '#title' => String::checkPlain($themes[$theme]->info['name']),
      '#attached' => array(
        'js' => array(
          array(
            // The block demonstration page is not marked as an administrative
            // page by path_is_admin() function in order to use the frontend
            // theme. Since JavaScript relies on a proper separation of admin
            // pages, it needs to know this is an actual administrative page.
            'data' => array('currentPathIsAdmin' => TRUE),
            'type' => 'setting',
          )
        ),
        'library' => array(
          'block/drupal.block.admin',
        ),
      ),
    );
  }

}
