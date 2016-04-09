<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Url;

/**
 * Default style plugin to render an OPML feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "opml",
 *   title = @Translation("OPML Feed"),
 *   help = @Translation("Generates an OPML feed from a view."),
 *   theme = "views_view_opml",
 *   display_types = {"feed"}
 * )
 */
class Opml extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $display = $this->view->displayHandlers->get($display_id);
    $url_options = array();
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();
    if ($display->hasPath()) {
      if (empty($this->preview)) {
        $build['#attached']['feed'][] = array($url, $title);
      }
    }
    else {
      $this->view->feedIcons[] = array(
        '#theme' => 'feed_icon',
        '#url' => $url,
        '#title' => $title,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->rowPlugin)) {
      debug('Drupal\views\Plugin\views\style\Opml: Missing row plugin');
      return;
    }
    $rows = array();

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    );
    unset($this->view->row_index);
    return $build;
  }

}
