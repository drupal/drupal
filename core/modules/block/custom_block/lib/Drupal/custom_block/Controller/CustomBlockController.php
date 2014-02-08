<?php

/**
 * @file
 * Contains \Drupal\custom_block\Controller\CustomBlockController
 */

namespace Drupal\custom_block\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\custom_block\CustomBlockTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomBlockController extends ControllerBase {

  /**
   * The custom block storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $customBlockStorage;

  /**
   * The custom block type storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $customBlockTypeStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorageController('custom_block'),
      $entity_manager->getStorageController('custom_block_type')
    );
  }

  /**
   * Constructs a CustomBlock object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $custom_block_storage
   *   The custom block storage controller.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $custom_block_type_storage
   *   The custom block type storage controller.
   */
  public function __construct(EntityStorageControllerInterface $custom_block_storage, EntityStorageControllerInterface $custom_block_type_storage) {
    $this->customBlockStorage = $custom_block_storage;
    $this->customBlockTypeStorage = $custom_block_type_storage;
  }

  /**
   * Displays add custom block links for available types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the custom block types that can be added or
   *   if there is only one custom block type defined for the site, the function
   *   returns the custom block add page for that custom block type.
   */
  public function add(Request $request) {
    $types = $this->customBlockTypeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }

    return array('#theme' => 'custom_block_add_list', '#content' => $types);
  }

  /**
   * Presents the custom block creation form.
   *
   * @param \Drupal\custom_block\CustomBlockTypeInterface $custom_block_type
   *   The custom block type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(CustomBlockTypeInterface $custom_block_type, Request $request) {
    $block = $this->customBlockStorage->create(array(
      'type' => $custom_block_type->id()
    ));
    if (($theme = $request->query->get('theme')) && in_array($theme, array_keys(list_themes()))) {
      // We have navigated to this page from the block library and will keep track
      // of the theme for redirecting the user to the configuration page for the
      // newly created block in the given theme.
      $block->setTheme($theme);
    }
    return $this->entityFormBuilder()->getForm($block);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\custom_block\CustomBlockTypeInterface $custom_block_type
   *   The custom block type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(CustomBlockTypeInterface $custom_block_type) {
    return $this->t('Add %type custom block', array('%type' => $custom_block_type->label()));
  }

}
