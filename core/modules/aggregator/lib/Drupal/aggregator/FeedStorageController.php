<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for aggregators feeds.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::create().
   */
  public function create(array $values) {
    $values += array(
      'link' => '',
      'description' => '',
      'image' => '',
    );
    return parent::create($values);
  }

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    parent::attachLoad($queried_entities, $load_revision);
    foreach ($queried_entities as $item) {
      $item->categories = db_query('SELECT c.cid, c.title FROM {aggregator_category} c JOIN {aggregator_category_feed} f ON c.cid = f.cid AND f.fid = :fid ORDER BY title', array(':fid' => $item->id()))->fetchAllKeyed();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::preDelete().
   */
  protected function preDelete($entities) {
    parent::preDelete($entities);

    // Invalidate the block cache to update aggregator feed-based derivatives.
    if (module_exists('block')) {
      drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
    }
    foreach ($entities as $entity) {
      $iids = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $entity->id()))->fetchCol();
      if ($iids) {
        entity_delete_multiple('aggregator_item', $iids);
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    parent::postDelete($entities);

    foreach ($entities as $entity) {
      // Make sure there is no active block for this feed.
      $block_configs = config_get_storage_names_with_prefix('plugin.core.block');
      foreach ($block_configs as $config_id) {
        $config = config($config_id);
        if ($config->get('id') == 'aggregator_feed_block:' . $entity->id()) {
          $config->delete();
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $entity) {
    parent::preSave($entity);

    // Invalidate the block cache to update aggregator feed-based derivatives.
    if (module_exists('block')) {
      drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
    }
    // An existing feed is being modified, delete the category listings.
    db_delete('aggregator_category_feed')
      ->condition('fid', $entity->id())
      ->execute();
  }

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    parent::postSave($entity, $update);

    if (!empty($entity->categories)) {
      foreach ($entity->categories as $cid => $value) {
        if ($value) {
          db_insert('aggregator_category_feed')
            ->fields(array(
              'fid' => $entity->id(),
              'cid' => $cid,
            ))
            ->execute();
        }
      }
    }
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
      'description' => t("The parent website's description that comes from the <description> element in the feed."),
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

}
