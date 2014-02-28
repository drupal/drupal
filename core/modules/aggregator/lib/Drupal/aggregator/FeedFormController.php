<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedFormController.
 */

namespace Drupal\aggregator;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the aggregator feed edit forms.
 */
class FeedFormController extends ContentEntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $feed = $this->entity;
    $intervals = array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200);
    $period = array_map('format_interval', array_combine($intervals, $intervals));
    $period[AGGREGATOR_CLEAR_NEVER] = $this->t('Never');

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $feed->label(),
      '#maxlength' => 255,
      '#description' => $this->t('The name of the feed (or the name of the website providing the feed).'),
      '#required' => TRUE,
    );

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $feed->language()->id,
      '#languages' => Language::STATE_ALL,
    );

    $form['url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $feed->getUrl(),
      '#maxlength' => NULL,
      '#description' => $this->t('The fully-qualified URL of the feed.'),
      '#required' => TRUE,
    );
    $form['refresh'] = array('#type' => 'select',
      '#title' => $this->t('Update interval'),
      '#default_value' => $feed->getRefreshRate(),
      '#options' => $period,
      '#description' => $this->t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );

    return parent::form($form, $form_state, $feed);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $feed = $this->buildEntity($form, $form_state);
    // Check for duplicate titles.
    $feed_storage_controller = $this->entityManager->getStorageController('aggregator_feed');
    $result = $feed_storage_controller->getFeedDuplicates($feed);
    foreach ($result as $item) {
      if (strcasecmp($item->title, $feed->label()) == 0) {
        $this->setFormError('title', $form_state, $this->t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $feed->label())));
      }
      if (strcasecmp($item->url, $feed->getUrl()) == 0) {
        $this->setFormError('url', $form_state, $this->t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $feed->getUrl())));
      }
    }
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $feed = $this->entity;
    $insert = (bool) $feed->id();
    $feed->save();
    if ($insert) {
      drupal_set_message($this->t('The feed %feed has been updated.', array('%feed' => $feed->label())));
      if (arg(0) == 'admin') {
        $form_state['redirect_route']['route_name'] = 'aggregator.admin_overview';
      }
      else {
        $form_state['redirect_route'] = $feed->urlInfo('canonical');
      }
    }
    else {
      watchdog('aggregator', 'Feed %feed added.', array('%feed' => $feed->label()), WATCHDOG_NOTICE, l($this->t('view'), 'admin/config/services/aggregator'));
      drupal_set_message($this->t('The feed %feed has been added.', array('%feed' => $feed->label())));
    }
  }

}
