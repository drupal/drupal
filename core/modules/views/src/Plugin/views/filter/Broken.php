<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\BrokenHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("broken")]
class Broken extends FilterPluginBase {
  use BrokenHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
  }

}
