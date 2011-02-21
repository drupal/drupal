<?php
// $Id: views_plugin_style_summary_unformatted.inc,v 1.1 2008/09/03 19:21:30 merlinofchaos Exp $
/**
 * @file
 * Contains the unformatted summary style plugin.
 */

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_summary_unformatted extends views_plugin_style_summary {
  function option_definition() {
    $options = parent::option_definition();
    $options['inline'] = array('default' => FALSE);
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

