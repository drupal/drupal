<?php

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\Plugin\views\display\DisplayMenuInterface;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides menu links for Views.
 *
 * @see \Drupal\views\Plugin\Menu\ViewsMenuLink
 */
class ViewsMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a \Drupal\views\Plugin\Derivative\ViewsLocalTask instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $viewStorage
   *   The view storage.
   */
  public function __construct(
    protected EntityStorageInterface $viewStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    $views = Views::getApplicableViews('uses_menu_links');

    foreach ($views as $data) {
      [$view_id, $display_id] = $data;
      /** @var \Drupal\views\ViewExecutable $executable */
      $executable = $this->viewStorage->load($view_id)->getExecutable();
      $executable->initDisplay();
      $display = $executable->displayHandlers->get($display_id);

      if (($display instanceof DisplayMenuInterface) && ($result = $display->getMenuLinks())) {
        foreach ($result as $link_id => $link) {
          $links[$link_id] = $link + $base_plugin_definition;
        }
      }
    }

    return $links;
  }

}
