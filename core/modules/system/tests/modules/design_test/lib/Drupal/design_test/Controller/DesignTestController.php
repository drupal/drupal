<?php
/**
 * @file
 * Contains \Drupal\design_test\Controller\DesignTestController.
 */

namespace Drupal\design_test\Controller;

use Drupal\Core\Controller\ControllerInterface;

/**
 * Controller routines for design_test routes.
 */
class DesignTestController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * Constructs a DesignTestController object.
   */
  public function __construct() {
  }

  /**
   * Menu for a category listing page.
   *
   * This is a specialized version of system_admin_menu_block_page(), which
   * retrieves all direct child menu links of the current page, regardless of
   * their type, skips default local tasks, and outputs them as a simple menu
   * tree as the main page content.
   *
   * @param string $category
   *   The design test category being currently accessed.
   *   Maps to the subdirectory names of this module.
   *
   * @return array
   *   A render array containing a menu link tree.
   */
  public function categoryPage($category) {
    $link = menu_link_get_preferred();
    $tree = menu_build_tree($link['menu_name'], array(
      'expanded' => array($link['mlid']),
      'min_depth' => $link['depth'] + 1,
      'max_depth' => $link['depth'] + 2,
    ));
    // Local tasks are hidden = -1, so normally not rendered in menu trees.
    foreach ($tree as &$data) {
      // Exclude default local tasks.
      if (!($data['link']['type'] & MENU_LINKS_TO_PARENT)) {
        $data['link']['hidden'] = 0;
      }
    }
    return menu_tree_output($tree);
  }

}
