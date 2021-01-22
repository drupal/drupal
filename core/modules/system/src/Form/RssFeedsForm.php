<?php

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure RSS settings for this site.
 *
 * @internal
 */
class RssFeedsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_rss_feeds_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.rss'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['feed_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed content'),
      '#default_value' => $this->config('system.rss')->get('items.view_mode'),
      '#options' => [
        'title' => $this->t('Titles only'),
        'teaser' => $this->t('Titles plus teaser'),
        'fulltext' => $this->t('Full text'),
      ],
      '#description' => $this->t('Global setting for the default display of content items in each feed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.rss')
      ->set('items.view_mode', $form_state->getValue('feed_view_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
