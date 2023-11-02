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
      '#config_target' => 'system.rss:items.view_mode',
      '#options' => [
        'title' => $this->t('Titles only'),
        'teaser' => $this->t('Titles plus teaser'),
        'fulltext' => $this->t('Full text'),
      ],
      '#description' => $this->t('Global setting for the default display of content items in each feed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
