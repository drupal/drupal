<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for aggregators feeds.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG implements FeedStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    parent::attachLoad($queried_entities, $load_revision);
    $this->loadCategories($queried_entities);
  }

  /**
   * Implements Drupal\Core\Entity\DataBaseStorageControllerNG::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields['fid'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the aggregor feed.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of the feed.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The feed language code.'),
      'type' => 'language_field',
    );
    $fields['url'] = array(
      'label' => t('URL'),
      'description' => t('The URL to the feed.'),
      'type' => 'uri_field',
    );
    $fields['refresh'] = array(
      'label' => t('Refresh'),
      'description' => t('How often to check for new feed items, in seconds.'),
      'type' => 'integer_field',
    );
    $fields['checked'] = array(
      'label' => t('Checked'),
      'description' => t('Last time feed was checked for new items, as Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['queued'] = array(
      'label' => t('Queued'),
      'description' => t('Time when this feed was queued for refresh, 0 if not queued.'),
      'type' => 'integer_field',
    );
    $fields['link'] = array(
      'label' => t('Link'),
      'description' => t('The link of the feed.'),
      'type' => 'uri_field',
    );
    $fields['description'] = array(
      'label' => t('Description'),
      'description' => t("The parent website's description that comes from the !description element in the feed.", array('!description' => '<description>')),
      'type' => 'string_field',
    );
    $fields['image'] = array(
      'label' => t('image'),
      'description' => t('An image representing the feed.'),
      'type' => 'uri_field',
    );
    $fields['hash'] = array(
      'label' => t('Hash'),
      'description' => t('Calculated hash of the feed data, used for validating cache.'),
      'type' => 'string_field',
    );
    $fields['etag'] = array(
      'label' => t('Etag'),
      'description' => t('Entity tag HTTP response header, used for validating cache.'),
      'type' => 'string_field',
    );
    $fields['modified'] = array(
      'label' => t('Modified'),
      'description' => t('When the feed was last modified, as a Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['block'] = array(
      'label' => t('Block'),
      'description' => t('Number of items to display in the feed’s block.'),
      'type' => 'integer_field',
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCategories(array $feeds) {
    foreach ($feeds as $feed) {
      $feed->categories = $this->database->query('SELECT c.cid, c.title FROM {aggregator_category} c JOIN {aggregator_category_feed} f ON c.cid = f.cid AND f.fid = :fid ORDER BY title', array(':fid' => $feed->id()))->fetchAllKeyed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveCategories(Feed $feed, array $categories) {
    foreach ($categories as $cid => $value) {
      if ($value) {
        $this->database->insert('aggregator_category_feed')
          ->fields(array(
            'fid' => $feed->id(),
            'cid' => $cid,
          ))
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCategories(array $feeds) {
    // An existing feed is being modified, delete the category listings.
    $this->database->delete('aggregator_category_feed')
      ->condition('fid', array_keys($feeds))
      ->execute();
  }

}
