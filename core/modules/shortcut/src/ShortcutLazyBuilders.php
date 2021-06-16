<?php

namespace Drupal\shortcut;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
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
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
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
   * @return array
   *   A renderable array of shortcut links.
   */
  public function lazyLinks() {
    $shortcut_set = shortcut_current_displayed_set();

    $links = shortcut_renderable_links();

    $configure_link = NULL;
    if (shortcut_set_edit_access($shortcut_set)->isAllowed()) {
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
