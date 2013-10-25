<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\Item.
 */

namespace Drupal\aggregator\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\aggregator\ItemInterface;

/**
 * Defines the aggregator item entity class.
 *
 * @EntityType(
 *   id = "aggregator_item",
 *   label = @Translation("Aggregator feed item"),
 *   module = "aggregator",
 *   controllers = {
 *     "storage" = "Drupal\aggregator\ItemStorageController",
 *     "view_builder" = "Drupal\aggregator\ItemViewBuilder"
 *   },
 *   base_table = "aggregator_item",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "iid",
 *     "label" = "title",
 *   }
 * )
 */
class Item extends ContentEntityBase implements ItemInterface {

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('iid')->value;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    parent::postCreate($storage_controller);

    if ($this->getPostedTime() === NULL) {
      $this->setPostedTime(REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    $storage_controller->saveCategories($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    $storage_controller->deleteCategories($entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['iid'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the aggregor item.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['fid'] = array(
      'label' => t('Aggregator feed ID'),
      'description' => t('The ID of the aggregator feed.'),
      'type' => 'integer_field',
    );
    $fields['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of the feed item.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The feed item language code.'),
      'type' => 'language_field',
    );
    $fields['link'] = array(
      'label' => t('Link'),
      'description' => t('The link of the feed item.'),
      'type' => 'uri_field',
    );
    $fields['author'] = array(
      'label' => t('Author'),
      'description' => t('The author of the feed item.'),
      'type' => 'string_field',
    );
    $fields['description'] = array(
      'label' => t('Description'),
      'description' => t('The body of the feed item.'),
      'type' => 'string_field',
    );
    $fields['timestamp'] = array(
      'label' => t('Posted timestamp'),
      'description' => t('Posted date of the feed item, as a Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['guid'] = array(
      'label' => t('GUID'),
      'description' => t('Unique identifier for the feed item.'),
      'type' => 'string_field',
    );
    return $fields;
  }

  /**
   * @inheritdoc
   */
  public function getFeedId() {
    return $this->get('fid')->value;
  }

  /**
   * @inheritdoc
   */
  public function setFeedId($fid) {
    return $this->set('fid', $fid);
  }

  /**
   * @inheritdoc
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * @inheritdoc
   */
  public function setTitle($title) {
    return $this->set('title', $title);
  }

  /**
   * @inheritdoc
   */
  public function  getLink() {
    return $this->get('link')->value;
  }

  /**
   * @inheritdoc
   */
  public function setLink($link) {
    return $this->set('link', $link);
  }

  /**
   * @inheritdoc
   */
  public function getAuthor() {
    return $this->get('author')->value;
  }

  /**
   * @inheritdoc
   */
  public function setAuthor($author) {
    return $this->set('author', $author);
  }

  /**
   * @inheritdoc
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * @inheritdoc
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * @inheritdoc
   */
  public function getPostedTime() {
    return $this->get('timestamp')->value;
  }

  /**
   * @inheritdoc
   */
  public function setPostedTime($timestamp) {
    return $this->set('timestamp', $timestamp);
  }

  /**
   * @inheritdoc
   */
  public function getGuid() {
    return $this->get('guid')->value;
  }

  /**
   * @inheritdoc
   */
  public function setGuid($guid) {
    return $this->set('guid', $guid);
  }
}
