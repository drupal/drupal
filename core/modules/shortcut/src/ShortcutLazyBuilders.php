<?php

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Lazy builders for the shortcut module.
 */
class ShortcutLazyBuilders implements TrustedCallbackInterface {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface|null $currentUser
   *   The current user.
   */
  public function __construct(RendererInterface $renderer, protected ?EntityTypeManagerInterface $entityTypeManager, protected ?AccountInterface $currentUser) {
    $this->renderer = $renderer;
    if (!isset($this->entityTypeManager)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entityTypeManager argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3427050', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    if (!isset($this->currentUser)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $currentUser argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3427050', E_USER_DEPRECATED);
      $this->currentUser = \Drupal::currentUser();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['lazyLinks'];
  }

  /**
   * #lazy_builder callback; builds shortcut toolbar links.
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
        '#title' => t('Edit shortcuts'),
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
