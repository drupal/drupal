<?php

/**
 * @file
 * Contains \Drupal\layout\Controller\LayoutController.
 */

namespace Drupal\layout\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\layout\Plugin\Type\LayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for layout routes.
 */
class LayoutController implements ControllerInterface {

  /**
   * Stores the Layout manager.
   *
   * @var \Drupal\layout\Plugin\Type\LayoutManager
   */
  protected $layoutManager;

  /**
   * Constructs a \Drupal\layout\Controller\LayoutController object.
   *
   * @param \Drupal\layout\Plugin\Type\LayoutManager $layout_manager
   *   The Layout manager.
   */
  function __construct(LayoutManager $layout_manager) {
    $this->layoutManager = $layout_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.layout'));
  }

  /**
   * Presents a list of layouts.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function layoutPageList() {
    // Get list of layouts defined by enabled modules and themes.
    $layouts = $this->layoutManager->getDefinitions();

    $rows = array();
    $header = array(t('Name'), t('Source'));
    foreach ($layouts as $name => $layout) {
      $provider_info = system_get_info($layout['provider']['type'], $layout['provider']['provider']);

      // Build table columns for this row.
      $row = array();
      $row['name'] = l($layout['title'], 'admin/structure/templates/manage/' . $name);
      // Type can either be 'module' or 'theme'.
      $row['provider'] = t('@name @type', array('@name' => $provider_info['name'], '@type' => t($layout['provider']['type'])));

      $rows[] = $row;
    }

    $build = array();
    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    return $build;

    // Ensure the provider types are translatable. These do not need to run,
    // just inform the static code parser of these source strings.
    t('module');
    t('theme');
  }

  /**
   * Demonstrates a layout template.
   *
   * @param string $key
   *   The key of the page layout being requested.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function layoutPageView($key) {
    $layout = $this->layoutManager->getDefinition($key);
    drupal_set_title(t('View template %name', array('%name' => $layout['title'])), PASS_THROUGH);

    // Render the layout in an admin context with region demonstrations.
    $instance = $this->layoutManager->createInstance($key, array());
    $regions = $instance->getRegions();
    foreach ($regions as $region => $info) {
      $regions[$region] = '<div class="layout-region-demonstration">' . String::checkPlain($info['label']) . '</div>';
    }
    $build['demonstration']['#markup'] = $instance->renderLayout(TRUE, $regions);

    // @todo Convert layout.admin.css to a library.
    $build['#attached']['css'][] = drupal_get_path('module', 'layout') . '/layout.admin.css';
    return $build;
  }

}
