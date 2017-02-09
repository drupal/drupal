<?php

namespace Drupal\menu_link_content\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver for user entered paths of menu links.
 *
 * The assumption is that the number of manually entered menu links are lower
 * compared to entity referenced ones.
 */
class MenuLinkContentDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a MenuLinkContentDeriver instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, MenuLinkManagerInterface $menu_link_manager) {
    $this->entityManager = $entity_manager;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get all custom menu links which should be rediscovered.
    $entity_ids = $this->entityManager->getStorage('menu_link_content')->getQuery()
      ->condition('rediscover', TRUE)
      ->execute();
    $plugin_definitions = [];
    $menu_link_content_entities = $this->entityManager->getStorage('menu_link_content')->loadMultiple($entity_ids);
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
    foreach ($menu_link_content_entities as $menu_link_content) {
      $plugin_definitions[$menu_link_content->uuid()] = $menu_link_content->getPluginDefinition();
    }
    return $plugin_definitions;
  }

}
