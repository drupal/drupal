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
        'library' => array(
          array('block', 'drupal.block.admin'),
        ),
      ),
    );
  }

}
