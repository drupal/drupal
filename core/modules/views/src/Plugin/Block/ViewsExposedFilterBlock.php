<?php

namespace Drupal\views\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Xss;

/**
 * Provides a 'Views Exposed Filter' block.
 *
 * @Block(
 *   id = "views_exposed_filter_block",
 *   admin_label = @Translation("Views Exposed Filter Block"),
 *   deriver = "Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock"
 * )
 */
class ViewsExposedFilterBlock extends ViewsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = $this->view->display_handler->getCacheMetadata()->getCacheContexts();
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   A renderable array representing the content of the block with additional
   *   context of current view and display ID.
   */
  public function build() {
    $output = $this->view->display_handler->viewExposedFormBlocks() ?? [];
    // Provide the context for block build and block view alter hooks.
    // \Drupal\views\Plugin\Block\ViewsBlock::build() adds the same context in
    // \Drupal\views\ViewExecutable::buildRenderable() using
    // \Drupal\views\Plugin\views\display\DisplayPluginBase::buildRenderable().
    if (!empty($output)) {
      $output += [
        '#view' => $this->view,
        '#display_id' => $this->displayID,
      ];
    }

    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    $this->addContextualLinks($output, 'exposed_filter');

    // Set the blocks title.
    if (!empty($this->configuration['label_display']) && ($this->view->getTitle() || !empty($this->configuration['views_label']))) {
      $output['#title'] = [
        '#markup' => empty($this->configuration['views_label']) ? $this->view->getTitle() : $this->configuration['views_label'],
        '#allowed_tags' => Xss::getHtmlTagList(),
      ];
    }

    return $output;
  }

}
