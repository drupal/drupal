<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\style\List.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "html_list",
 *   title = @Translation("HTML List"),
 *   help = @Translation("Displays rows as HTML list."),
 *   theme = "views_view_list",
 *   display_types = {"normal"}
 * )
 */
class HtmlList extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = array('default' => 'ul');
    $options['class'] = array('default' => '');

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('List type'),
      '#options' => array('ul' => $this->t('Unordered list'), 'ol' => $this->t('Ordered list')),
      '#default_value' => $this->options['type'],
    );
    $form['class'] = array(
      '#title' => $this->t('List class'),
      '#description' => $this->t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    );
  }

}
