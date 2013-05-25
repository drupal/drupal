<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedFormController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\Core\Language\Language;

/**
 * Form controller for the aggregator feed edit forms.
 */
class FeedFormController extends EntityFormControllerNG {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $feed = $this->entity;
    $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');
    $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $feed->label(),
      '#maxlength' => 255,
      '#description' => t('The name of the feed (or the name of the website providing the feed).'),
      '#required' => TRUE,
    );

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $feed->language()->langcode,
      '#languages' => Language::STATE_ALL,
    );

    $form['url'] = array(
      '#type' => 'url',
      '#title' => t('URL'),
      '#default_value' => $feed->url->value,
      '#maxlength' => NULL,
      '#description' => t('The fully-qualified URL of the feed.'),
      '#required' => TRUE,
    );
    $form['refresh'] = array('#type' => 'select',
      '#title' => t('Update interval'),
      '#default_value' => $feed->refresh->value,
      '#options' => $period,
      '#description' => t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );
    $form['block'] = array('#type' => 'select',
      '#title' => t('News items in block'),
      '#default_value' => $feed->block->value,
      '#options' => drupal_map_assoc(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20)),
      '#description' => t("Drupal can make a block with the most recent news items of this feed. You can <a href=\"@block-admin\">configure blocks</a> to be displayed in the sidebar of your page. This setting lets you configure the number of news items to show in this feed's block. If you choose '0' this feed's block will be disabled.", array('@block-admin' => url('admin/structure/block'))),
    );

    // Handling of categories.
    $options = array();
    $values = array();
    $categories = db_query('SELECT c.cid, c.title FROM {aggregator_category} c ORDER BY title');
    foreach ($categories as $category) {
      $options[$category->cid] = check_plain($category->title);
      if (!empty($feed->categories) && in_array($category->cid, array_keys($feed->categories))) {
        $values[] = $category->cid;
      }
    }

    if ($options) {
      $form['category'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Categorize news items'),
        '#default_value' => $values,
        '#options' => $options,
        '#description' => t('New feed items are automatically filed in the checked categories.'),
      );
    }

    return parent::form($form, $form_state, $feed);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $feed = $this->buildEntity($form, $form_state);
    // Check for duplicate titles.
    if ($feed->id()) {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE (title = :title OR url = :url) AND fid <> :fid", array(':title' => $feed->label(), ':url' => $feed->url->value, ':fid' => $feed->id()));
    }
    else {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE title = :title OR url = :url", array(':title' => $feed->label(), ':url' => $feed->url->value));
    }

    foreach ($result as $item) {
      if (strcasecmp($item->title, $feed->label()) == 0) {
        form_set_error('title', t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $feed->label())));
      }
      if (strcasecmp($item->url, $feed->url->value) == 0) {
        form_set_error('url', t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $feed->url->value)));
      }
    }
    parent::validate($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $feed = $this->entity;
    $insert = (bool) $feed->id();
    if (!empty($form_state['values']['category'])) {
      // Store category values for post save operations.
      // @see Drupal\Core\Entity\FeedStorageController::postSave()
      $feed->categories = $form_state['values']['category'];
    }
    $feed->save();
    if ($insert) {
      drupal_set_message(t('The feed %feed has been updated.', array('%feed' => $feed->label())));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator';
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/' . $feed->id();
      }
    }
    else {
      watchdog('aggregator', 'Feed %feed added.', array('%feed' => $feed->label()), WATCHDOG_NOTICE, l(t('view'), 'admin/config/services/aggregator'));
      drupal_set_message(t('The feed %feed has been added.', array('%feed' => $feed->label())));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/config/services/aggregator/delete/feed/' . $this->entity->id();
  }

}
