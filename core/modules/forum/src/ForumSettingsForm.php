<?php

/**
 * @file
 * Contains \Drupal\forum\ForumSettingsForm.
 */

namespace Drupal\forum;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure forum settings for this site.
 */
class ForumSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forum_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('forum.settings');

    $options = array(5, 10, 15, 20, 25, 30, 35, 40, 50, 60, 80, 100, 150, 200, 250, 300, 350, 400, 500);
    $form['forum_hot_topic'] = array(
      '#type' => 'select',
      '#title' => $this->t('Hot topic threshold'),
      '#default_value' => $config->get('topics.hot_threshold'),
      '#options' => array_combine($options, $options),
      '#description' => $this->t('The number of replies a topic must have to be considered "hot".'),
    );
    $options = array(10, 25, 50, 75, 100);
    $form['forum_per_page'] = array(
      '#type' => 'select',
      '#title' => $this->t('Topics per page'),
      '#default_value' => $config->get('topics.page_limit'),
      '#options' => array_combine($options, $options),
      '#description' => $this->t('Default number of forum topics displayed per page.'),
    );
    $forder = array(
      1 => $this->t('Date - newest first'),
      2 => $this->t('Date - oldest first'),
      3 => $this->t('Posts - most active first'),
      4 => $this->t('Posts - least active first')
    );
    $form['forum_order'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default order'),
      '#default_value' => $config->get('topics.order'),
      '#options' => $forder,
      '#description' => $this->t('Default display order for topics.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('forum.settings')
      ->set('topics.hot_threshold', $form_state['values']['forum_hot_topic'])
      ->set('topics.page_limit', $form_state['values']['forum_per_page'])
      ->set('topics.order', $form_state['values']['forum_order'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
