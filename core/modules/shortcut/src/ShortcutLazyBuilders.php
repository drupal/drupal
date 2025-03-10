<?php

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Lazy builders for the shortcut module.
 */
class ShortcutLazyBuilders implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ShortcutLazyBuilders object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(RendererInterface $renderer, protected EntityTypeManagerInterface $entityTypeManager, protected AccountInterface $currentUser) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['lazyLinks'];
  }

  /**
   * Render API callback: Builds shortcut toolbar links.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @param bool $show_configure_link
   *   Boolean to indicate whether to include the configure link or not.
   *
   * @return array
   *   A renderable array of shortcut links.
   */
  public function lazyLinks(bool $show_configure_link = TRUE) {
    $shortcut_set = $this->entityTypeManager->getStorage('shortcut_set')
      ->getDisplayedToUser($this->currentUser);

    $links = shortcut_renderable_links();

    $configure_link = NULL;
    if ($show_configure_link && shortcut_set_edit_access($shortcut_set)->isAllowed()) {
      $configure_link = [
        '#type' => 'link',
        '#title' => $this->t('Edit shortcuts'),
        '#url' => Url::fromRoute('entity.shortcut_set.customize_form', ['shortcut_set' => $shortcut_set->id()]),
        '#options' => ['attributes' => ['class' => ['edit-shortcuts']]],
      ];
    }

    $build = [
      'shortcuts' => $links,
      'configure' => $configure_link,
    ];

    $this->renderer->addCacheableDependency($build, $shortcut_set);

    return $build;
  }

}
