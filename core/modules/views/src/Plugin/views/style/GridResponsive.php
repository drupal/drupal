<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item in a responsive grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "grid_responsive",
 *   title = @Translation("Responsive Grid"),
 *   help = @Translation("Displays rows in a responsive grid."),
 *   theme = "views_view_grid_responsive",
 *   display_types = {"normal"}
 * )
 */
class GridResponsive extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = ['default' => '4'];
    $options['cell_min_width'] = ['default' => '100'];
    $options['grid_gutter'] = ['default' => '10'];
    $options['alignment'] = ['default' => 'horizontal'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of columns'),
      '#attributes' => ['style' => 'width: 6em;'],
      '#description' => $this->t('The maximum number of columns that will be displayed within the grid.'),
      '#default_value' => $this->options['columns'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['cell_min_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum grid cell width'),
      '#field_suffix' => 'px',
      '#attributes' => ['style' => 'width: 6em;'],
      '#description' => $this->t('The minimum width of the grid cells. If the grid container becomes narrow, the grid cells will reflow onto the next row as needed.'),
      '#default_value' => $this->options['cell_min_width'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['grid_gutter'] = [
      '#type' => 'number',
      '#title' => $this->t('Grid gutter spacing'),
      '#field_suffix' => 'px',
      '#attributes' => ['style' => 'width: 6em;'],
      '#description' => $this->t('The spacing between the grid cells.'),
      '#default_value' => $this->options['grid_gutter'],
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['alignment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Alignment'),
      '#options' => ['horizontal' => $this->t('Horizontal'), 'vertical' => $this->t('Vertical')],
      '#default_value' => $this->options['alignment'],
      '#description' => $this->t('Horizontal alignment will place items starting in the upper left and moving right. Vertical alignment will place items starting in the upper left and moving down.'),
    ];
  }

}
