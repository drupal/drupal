<?php

/**
 * @file
 * Contains \Drupal\block_content\Controller\BlockContentController
 */

namespace Drupal\block_content\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\block_content\BlockContentTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class BlockContentController extends ControllerBase {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The custom block type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('block_content'),
      $entity_manager->getStorage('block_content_type')
    );
  }

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The custom block type storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage, EntityStorageInterface $block_content_type_storage) {
    $this->blockContentStorage = $block_content_storage;
    $this->blockContentTypeStorage = $block_content_type_storage;
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
    $types = $this->blockContentTypeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }

    return array('#theme' => 'block_content_add_list', '#content' => $types);
  }

  /**
   * Presents the custom block creation form.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The custom block type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(BlockContentTypeInterface $block_content_type, Request $request) {
    $block = $this->blockContentStorage->create(array(
      'type' => $block_content_type->id()
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
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The custom block type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(BlockContentTypeInterface $block_content_type) {
    return $this->t('Add %type custom block', array('%type' => $block_content_type->label()));
  }

}
