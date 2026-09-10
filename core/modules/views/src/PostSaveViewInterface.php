<?php

namespace Drupal\views;

use Drupal\views\Plugin\views\display\DisplayPluginInterface;

/**
 * Provides an interface for views plugins.
 */
interface PostSaveViewInterface {

  /**
   * Acts on a saved view.
   *
   * @param \Drupal\views\PostSaveProcess $postSaveProcess
   *   The views save context. Plugins flag work here instead of performing
   *   it directly, so it happens at most once per view save. For example,
   *   the block plugin queues the block plugin manager via
   *   ::addDiscoveryToClear() even if more than one block display changed.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface|null $original_display
   *   (optional) The original display. Empty when saving a new view.
   *
   * @see \Drupal\views\Entity\View::postSave()
   */
  public function postSaveView(PostSaveProcess $postSaveProcess, ?DisplayPluginInterface $original_display = NULL): void;

}
