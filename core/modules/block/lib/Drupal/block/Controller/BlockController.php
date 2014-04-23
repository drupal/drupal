<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockController.
 */

namespace Drupal\block\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for admin block routes.
 */
class BlockController extends ControllerBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new BlockController instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

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
    return array(
      '#title' => String::checkPlain($this->themeHandler->getName($theme)),
      '#attached' => array(
        'js' => array(
          array(
            // The block demonstration page is not marked as an administrative
            // page by path_is_admin() function in order to use the frontend
            // theme. Since JavaScript relies on a proper separation of admin
            // pages, it needs to know this is an actual administrative page.
            'data' => array('path' => array('currentPathIsAdmin' => TRUE)),
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
