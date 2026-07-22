<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Traits;

/**
 * Provides a method to enable the promoted_content view.
 *
 * @todo Revisit tests using this trait.
 *   https://www.drupal.org/project/drupal/issues/3592096
 */
trait PromotedContentViewTestTrait {

  /**
   * Enables the promoted_content view and optionally rebuilds the router.
   *
   * The promoted_content view is disabled by default so tests that rely on
   * the front page or RSS feed provided by this view need to explicitly
   * enable it and rebuild the router so the routes become available.
   *
   * @param bool $rebuildRouter
   *   Whether to rebuild the router after enabling the view. Defaults to TRUE.
   *
   * @return \Drupal\views\Entity\View
   *   The enabled view entity.
   */
  protected function enablePromotedContentView(bool $rebuildRouter = TRUE) {
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('promoted_content');
    $view->enable()->save();
    if ($rebuildRouter) {
      \Drupal::service('router.builder')->rebuild();
    }
    return $view;
  }

}
