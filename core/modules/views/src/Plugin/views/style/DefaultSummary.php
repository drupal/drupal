<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "default_summary",
 *   title = @Translation("List"),
 *   help = @Translation("Displays the default summary as a list."),
 *   theme = "views_view_summary",
 *   display_types = {"summary"}
 * )
 */
class DefaultSummary extends StylePluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['base_path'] = ['default' => ''];
    $options['count'] = ['default' => TRUE];
    $options['override'] = ['default' => FALSE];
    $options['items_per_page'] = ['default' => 25];

    return $options;
  }

  public function query() {
    if (!empty($this->options['override'])) {
      $this->view->setItemsPerPage(intval($this->options['items_per_page']));
    }
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#default_value' => $this->options['base_path'],
      '#description' => $this->t('Define the base path for links in this summary
        view, i.e. http://example.com/<strong>your_view_path/archive</strong>.
        Do not include beginning and ending forward slash. If this value
        is empty, views will use the first path found as the base path,
        in page displays, or / if no path could be found.'),
    ];
    $form['count'] = [
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['count']),
      '#title' => $this->t('Display record count with link'),
    ];
    $form['override'] = [
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['override']),
      '#title' => $this->t('Override number of items to display'),
    ];

    $form['items_per_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items to display'),
      '#default_value' => $this->options['items_per_page'],
      '#states' => [
        'visible' => [
          ':input[name="options[summary][options][' . $this->definition['id'] . '][override]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  public function render() {
    $rows = [];
    foreach ($this->view->result as $row) {
      // @todo: Include separator as an option.
      $rows[] = $row;
    }

    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
  }

}
