<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\style\UnformattedSummary.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "unformatted_summary",
 *   title = @Translation("Unformatted"),
 *   help = @Translation("Displays the summary unformatted, with option for one after another or inline."),
 *   theme = "views_view_summary_unformatted",
 *   display_types = {"summary"}
 * )
 */
class UnformattedSummary extends DefaultSummary {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['inline'] = array('default' => FALSE);
    $options['separator'] = array('default' => '');
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['inline'] = array(
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['inline']),
      '#title' => $this->t('Display items inline'),
    );
    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $this->options['separator'],
    );
  }

}
