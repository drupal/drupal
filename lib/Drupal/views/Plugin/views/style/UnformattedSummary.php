<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\style\UnformattedSummary.
 */

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "unformatted_summary",
 *   title = @Translation("Unformatted"),
 *   help = @Translation("Displays the summary unformatted, with option for one after another or inline."),
 *   theme = "views_view_summary_unformatted",
 *   type = "summary",
 *   help_topic = "style-summary-unformatted"
 * )
 */
class UnformattedSummary extends DefaultSummary {

  function option_definition() {
    $options = parent::option_definition();
    $options['inline'] = array('default' => FALSE, 'bool' => TRUE);
    $options['separator'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['inline'] = array(
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['inline']),
      '#title' => t('Display items inline'),
    );
    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator'),
      '#default_value' => $this->options['separator'],
    );
  }

}
