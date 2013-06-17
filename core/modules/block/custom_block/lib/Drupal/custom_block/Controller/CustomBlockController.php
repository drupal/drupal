<?php

/**
 * @file
 * Contains \Drupal\custom_block\Controller\CustomBlockController
 */

namespace Drupal\custom_block\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\custom_block\CustomBlockTypeInterface;
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
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $container->get('request'),
      $entity_manager->getStorageController('custom_block'),
      $entity_manager->getStorageController('custom_block_type')
    );
  }

  /**
   * Constructs a CustomBlock object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $custom_block_storage
   *   The custom block storage controller.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $custom_block_type_storage
   *   The custom block type storage controller.
   */
  public function __construct(Request $request, EntityStorageControllerInterface $custom_block_storage, EntityStorageControllerInterface $custom_block_type_storage) {
    $this->request = $request;
    $this->customBlockStorage = $custom_block_storage;
    $this->customBlockTypeStorage = $custom_block_type_storage;
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
    $types = $this->customBlockTypeStorage->load();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type);
    }

    return array('#theme' => 'custom_block_add_list', '#content' => $types);
  }

  /**
   * Presents the custom block creation form.
   *
   * @param \Drupal\custom_block\CustomBlockTypeInterface $custom_block_type
   *   The custom block type to add.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(CustomBlockTypeInterface $custom_block_type) {
    // @todo Remove this when https://drupal.org/node/1981644 is in.
    drupal_set_title(t('Add %type custom block', array(
      '%type' => $custom_block_type->label()
    )), PASS_THROUGH);
    $block = $this->customBlockStorage->create(array(
      'type' => $custom_block_type->id()
    ));
    if (($theme = $this->request->attributes->get('theme')) && in_array($theme, array_keys(list_themes()))) {
      // We have navigated to this page from the block library and will keep track
      // of the theme for redirecting the user to the configuration page for the
      // newly created block in the given theme.
      $block->setTheme($theme);
    }
    return entity_get_form($block);
  }

}
