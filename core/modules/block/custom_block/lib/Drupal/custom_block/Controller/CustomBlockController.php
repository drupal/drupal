<?php

/**
 * @file
 * Contains \Drupal\custom_block\Controller\CustomBlockController
 */

namespace Drupal\custom_block\Controller;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomBlockController implements ControllerInterface {

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Entity Manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request'),
      $container->get('plugin.manager.entity'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a CustomBlock object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module Handler service.
   */
  public function __construct(Request $request, EntityManager $entity_manager, ModuleHandler $module_handler) {
    $this->request = $request;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Displays add custom block links for available types.
   *
   * @return array
   *   A render array for a list of the custom block types that can be added or
   *   if there is only one custom block type defined for the site, the function
   *   returns the custom block add page for that custom block type.
   */
  public function add() {
    $options = array();
    if (($theme = $this->request->attributes->get('theme')) && in_array($theme, array_keys(list_themes()))) {
      // We have navigated to this page from the block library and will keep track
      // of the theme for redirecting the user to the configuration page for the
      // newly created block in the given theme.
      $options = array(
        'query' => array('theme' => $theme)
      );
    }
    $types = $this->entityManager->getStorageController('custom_block_type')->load();
    if ($types && count($types) == 1) {
      $type = reset($types);
      // @todo convert this to OO once block/add/%type uses a Controller. Will
      //   be fixed in http://drupal.org/node/1978166.
      $this->moduleHandler->loadInclude('custom_block', 'pages.inc');
      return custom_block_add($type);
    }

    return array('#theme' => 'custom_block_add_list', '#content' => $types);
  }

}
