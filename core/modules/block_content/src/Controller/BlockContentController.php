<?php

namespace Drupal\block_content\Controller;

use Drupal\block_content\BlockContentTypeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
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
   * Displays add content block links for available types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the block types that can be added or
   *   if there is only one block type defined for the site, the function
   *   returns the content block add page for that block type.
   */
  public function add(Request $request) {
    // @todo deprecate see https://www.drupal.org/project/drupal/issues/3346394.
    $types = [];
    // Only use block types the user has access to.
    foreach ($this->blockContentTypeStorage->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('block_content')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $types[$type->id()] = $type;
      }
    }
    uasort($types, [$this->blockContentTypeStorage->getEntityType()->getClass(), 'sort']);
    if ($types && count($types) == 1) {
      $type = reset($types);
      $query = $request->query->all();
      return $this->redirect('block_content.add_form', ['block_content_type' => $type->id()], ['query' => $query]);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }

    return ['#theme' => 'block_content_add_list', '#content' => $types];
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
   */
  public function getAddFormTitle(BlockContentTypeInterface $block_content_type) {
    return $this->t('Add %type content block', ['%type' => $block_content_type->label()]);
  }

}
