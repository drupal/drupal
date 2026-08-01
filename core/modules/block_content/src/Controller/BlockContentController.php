<?php

namespace Drupal\block_content\Controller;

use Drupal\block_content\BlockContentTypeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for custom block routes.
 */
class BlockContentController extends ControllerBase {

  /**
   * The content block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The content block type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content'),
      $entity_type_manager->getStorage('block_content_type'),
      $container->get('theme_handler')
    );
  }

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The content block storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The block type storage.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(EntityStorageInterface $block_content_storage, EntityStorageInterface $block_content_type_storage, ThemeHandlerInterface $theme_handler) {
    $this->blockContentStorage = $block_content_storage;
    $this->blockContentTypeStorage = $block_content_type_storage;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Presents the content block creation form.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The block type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  #[Route(
    path: '/block/add/{block_content_type}',
    name: 'block_content.add_form',
    title: static function (BlockContentTypeInterface $block_content_type) {
      return new TranslatableMarkup('Add %type content block', ['%type' => $block_content_type->label()]);
    },
    requirements: ['_entity_create_access' => 'block_content:{block_content_type}'],
    options: ['_admin_route' => TRUE],
  )]
  public function addForm(BlockContentTypeInterface $block_content_type, Request $request) {
    $block = $this->blockContentStorage->create([
      'type' => $block_content_type->id(),
    ]);
    if (($theme = $request->query->get('theme')) && in_array($theme, array_keys($this->themeHandler->listInfo()))) {
      // We have navigated to this page from the block library and will keep
      // track of the theme for redirecting the user to the configuration page
      // for the newly created block in the given theme.
      $block->setTheme($theme);
    }
    return $this->entityFormBuilder()->getForm($block);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The block type being added.
   *
   * @return string
   *   The page title.
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use the
   *   title closure on the route attribute instead.
   *
   * @see https://www.drupal.org/node/3614321
   */
  public function getAddFormTitle(BlockContentTypeInterface $block_content_type) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use the title closure on the route attribute instead. See https://www.drupal.org/node/3614321', E_USER_DEPRECATED);
    return $this->t('Add %type content block', ['%type' => $block_content_type->label()]);
  }

}
