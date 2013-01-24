<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\pager\Mini.
 */

namespace Drupal\views\Plugin\views\pager;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The plugin to handle mini pager.
 *
 * @ingroup views_pager_plugins
 *
 * @Plugin(
 *   id = "mini",
 *   title = @Translation("Paged output, mini pager"),
 *   short_title = @Translation("Mini"),
 *   help = @Translation("Use the mini pager output.")
 * )
 */
class Mini extends SqlBase {

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPlugin::defineOptions().
   *
   * Provides sane defaults for the next/previous links.
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['tags']['contains']['previous']['default'] = '‹‹';
    $options['tags']['contains']['next']['default'] = '››';

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPluginBase::summaryTitle().
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return format_plural($this->options['items_per_page'], 'Mini pager, @count item, skip @skip', 'Mini pager, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return format_plural($this->options['items_per_page'], 'Mini pager, @count item', 'Mini pager, @count items', array('@count' => $this->options['items_per_page']));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\pager\PagerPluginBase::render().
   */
  function render($input) {
    $pager_theme = views_theme_functions('views_mini_pager', $this->view, $this->view->display_handler->display);
    // The 1, 3 index are correct, see theme_pager().
    $tags = array(
      1 => $this->options['tags']['previous'],
      3 => $this->options['tags']['next'],
    );
    $output = theme($pager_theme, array(
      'parameters' => $input,
      'element' => $this->options['id'],
      'tags' => $tags,
    ));
    return $output;
  }

}
