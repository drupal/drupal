<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;

/**
 * The default style plugin for summaries.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "unformatted_summary",
  title: new TranslatableMarkup("Unformatted"),
  help: new TranslatableMarkup("Displays the summary unformatted, with option for one after another or inline."),
  theme: "views_view_summary_unformatted",
  display_types: ["summary"],
)]
class UnformattedSummary extends DefaultSummary {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['inline'] = ['default' => FALSE];
    $options['separator'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['inline'] = [
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['inline']),
      '#title' => $this->t('Display items inline'),
    ];
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $this->options['separator'],
    ];
  }

}
