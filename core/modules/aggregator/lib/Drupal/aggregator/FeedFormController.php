<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedFormController.
 */

namespace Drupal\aggregator;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Language\Language;
use Drupal\aggregator\CategoryStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the aggregator feed edit forms.
 */
class FeedFormController extends ContentEntityFormController {

  /**
   * The feed storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $feedStorageController;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorageController;

  /**
   * Constructs a FeedForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $feed_storage
   *   The feed storage.
   * @param \Drupal\aggregator\CategoryStorageControllerInterface $category_storage_controller
   *   The category storage controller.
   */
  public function __construct(EntityStorageControllerInterface $feed_storage, CategoryStorageControllerInterface $category_storage_controller) {
    $this->feedStorageController = $feed_storage;
    $this->categoryStorageController = $category_storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')->getStorageController('aggregator_feed'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $feed = $this->entity;
    $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');
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
      '#default_value' => $feed->url->value,
      '#maxlength' => NULL,
      '#description' => $this->t('The fully-qualified URL of the feed.'),
      '#required' => TRUE,
    );
    $form['refresh'] = array('#type' => 'select',
      '#title' => $this->t('Update interval'),
      '#default_value' => $feed->refresh->value,
      '#options' => $period,
      '#description' => $this->t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );

    // Handling of categories.
    $options = array();
    $values = array();
    $categories = $this->categoryStorageController->loadAllKeyed();
    foreach ($categories as $cid => $title) {
      $options[$cid] = String::checkPlain($title);
      if (!empty($feed->categories) && in_array($cid, array_keys($feed->categories))) {
        $values[] = $cid;
      }
    }

    if ($options) {
      $form['category'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Categorize news items'),
        '#default_value' => $values,
        '#options' => $options,
        '#description' => $this->t('New feed items are automatically filed in the checked categories.'),
      );
    }

    return parent::form($form, $form_state, $feed);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $feed = $this->buildEntity($form, $form_state);
    // Check for duplicate titles.
    $result = $this->feedStorageController->getFeedDuplicates($feed);
    foreach ($result as $item) {
      if (strcasecmp($item->title, $feed->label()) == 0) {
        form_set_error('title', $this->t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $feed->label())));
      }
      if (strcasecmp($item->url, $feed->url->value) == 0) {
        form_set_error('url', $this->t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $feed->url->value)));
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
    if (!empty($form_state['values']['category'])) {
      // Store category values for post save operations.
      // @see \Drupal\Core\Entity\FeedStorageController::postSave()
      $feed->categories = $form_state['values']['category'];
    }
    $feed->save();
    if ($insert) {
      drupal_set_message($this->t('The feed %feed has been updated.', array('%feed' => $feed->label())));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator';
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/' . $feed->id();
      }
    }
    else {
      watchdog('aggregator', 'Feed %feed added.', array('%feed' => $feed->label()), WATCHDOG_NOTICE, l($this->t('view'), 'admin/config/services/aggregator'));
      drupal_set_message($this->t('The feed %feed has been added.', array('%feed' => $feed->label())));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/config/services/aggregator/delete/feed/' . $this->entity->id();
  }

}
