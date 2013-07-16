<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\style\Grid.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Style plugin to render each item in a grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "grid",
 *   title = @Translation("Grid"),
 *   help = @Translation("Displays rows in a grid."),
 *   theme = "views_view_grid",
 *   display_types = {"normal"}
 * )
 */
class Grid extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = array('default' => '4');
    $options['automatic_width'] = array('default' => TRUE);
    $options['alignment'] = array('default' => 'horizontal');
    $options['col_class_custom'] = array('default' => '');
    $options['col_class_default'] = array('default' => TRUE);
    $options['row_class_custom'] = array('default' => '');
    $options['row_class_default'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['columns'] = array(
      '#type' => 'number',
      '#title' => t('Number of columns'),
      '#default_value' => $this->options['columns'],
      '#required' => TRUE,
      '#min' => 1,
    );
    $form['automatic_width'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatic width'),
      '#description' => t('The width of each column will be calculated automatically based on the number of columns provided. If additional classes are entered or a theme injects classes based on a grid system, disabling this option may prove beneficial.'),
      '#default_value' => $this->options['automatic_width'],
    );
    $form['alignment'] = array(
      '#type' => 'radios',
      '#title' => t('Alignment'),
      '#options' => array('horizontal' => t('Horizontal'), 'vertical' => t('Vertical')),
      '#default_value' => $this->options['alignment'],
      '#description' => t('Horizontal alignment will place items starting in the upper left and moving right. Vertical alignment will place items starting in the upper left and moving down.'),
    );
    $form['col_class_default'] = array(
      '#title' => t('Default column classes'),
      '#description' => t('Add the default views column classes like views-col, col-1 and clearfix to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['col_class_default'],
    );
    $form['col_class_custom'] = array(
      '#title' => t('Custom column class'),
      '#description' => t('Additional classes to provide on each column. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['col_class_custom'],
    );
    if ($this->usesFields()) {
      $form['col_class_custom']['#description'] .= ' ' . t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
    $form['row_class_default'] = array(
      '#title' => t('Default row classes'),
      '#description' => t('Adds the default views row classes like views-row, row-1 and clearfix to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['row_class_default'],
    );
    $form['row_class_custom'] = array(
      '#title' => t('Custom row class'),
      '#description' => t('Additional classes to provide on each row. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['row_class_custom'],
    );
    if ($this->usesFields()) {
      $form['row_class_custom']['#description'] .= ' ' . t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
  }

}
