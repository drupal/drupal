<?php
/**
 * @file
 * Contains \Drupal\layout_test\Controller\LayoutTestController.
 */

namespace Drupal\layout_test\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for layout_test routes.
 */
class LayoutTestController implements ControllerInterface{

  /**
   * Stores the entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $entityStorageController;

  /**
   * Constructs a \Drupal\layout_test\Controller\LayoutTestController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $entity_storage_controller
   *   The entity storage controller.
   */
  function __construct(EntityStorageControllerInterface $entity_storage_controller) {
    $this->entityStorageController = $entity_storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.entity')->getStorageController('display'));
  }

  /**
   * Displays basic page for layout testing purposes.
   *
   * @return string
   *   An HTML string representing the contents of layout_test page.
   */
  public function layoutTestPage() {
    // Hack to enable and apply the theme to this page and manually invoke its
    // layout plugin and render it.
    global $theme;
    $theme = 'layout_test_theme';
    theme_enable(array($theme));

    $display = $this->entityStorageController->load('test_twocol');
    $layout = $display->getLayoutInstance();

    // @todo This tests that the layout can render its regions, but does not test
    //   block rendering: http://drupal.org/node/1812720.
    // Add sample content in the regions that is looked for in the tests.
    $regions = $layout->getRegions();
    foreach ($regions as $region => $info) {
      $regions[$region] = '<h3>' . $info['label'] . '</h3>';
    }

    return $layout->renderLayout(FALSE, $regions);
  }

}
