<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\Item.
 */

namespace Drupal\aggregator\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\aggregator\ItemInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * Defines the aggregator item entity class.
 *
 * @ContentEntityType(
 *   id = "aggregator_item",
 *   label = @Translation("Aggregator feed item"),
 *   controllers = {
 *     "storage" = "Drupal\aggregator\ItemStorage",
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
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['iid'] = FieldDefinition::create('integer')
      ->setLabel(t('Aggregator item ID'))
      ->setDescription(t('The ID of the feed item.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['fid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Aggregator feed ID'))
      ->setDescription(t('The ID of the aggregator feed.'))
      ->setSetting('target_type', 'aggregator_feed');

    $fields['title'] = FieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the feed item.'));

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The feed item language code.'));

    $fields['link'] = FieldDefinition::create('uri')
      ->setLabel(t('Link'))
      ->setDescription(t('The link of the feed item.'));

    $fields['author'] = FieldDefinition::create('string')
      ->setLabel(t('Author'))
      ->setDescription(t('The author of the feed item.'));

    // @todo Convert to a text field in https://drupal.org/node/2149845.
    $fields['description'] = FieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The body of the feed item.'));

    $fields['timestamp'] = FieldDefinition::create('created')
      ->setLabel(t('Posted timestamp'))
      ->setDescription(t('Posted date of the feed item, as a Unix timestamp.'));

    // @todo Convert to a real UUID field in https://drupal.org/node/2149851.
    $fields['guid'] = FieldDefinition::create('string')
      ->setLabel(t('GUID'))
      ->setDescription(t('Unique identifier for the feed item.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedId() {
    return $this->get('fid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setFeedId($fid) {
    return $this->set('fid', $fid);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    return $this->set('title', $title);
  }

  /**
   * {@inheritdoc}
   */
  public function  getLink() {
    return $this->get('link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLink($link) {
    return $this->set('link', $link);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor() {
    return $this->get('author')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthor($author) {
    return $this->set('author', $author);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getPostedTime() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostedTime($timestamp) {
    return $this->set('timestamp', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getGuid() {
    return $this->get('guid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGuid($guid) {
    return $this->set('guid', $guid);
  }
}
