<?php

namespace Drupal\aggregator\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\aggregator\ItemInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;

/**
 * Defines the aggregator item entity class.
 *
 * @ContentEntityType(
 *   id = "aggregator_item",
 *   label = @Translation("Aggregator feed item"),
 *   handlers = {
 *     "storage" = "Drupal\aggregator\ItemStorage",
 *     "storage_schema" = "Drupal\aggregator\ItemStorageSchema",
 *     "view_builder" = "Drupal\aggregator\ItemViewBuilder",
 *     "access" = "Drupal\aggregator\FeedAccessControlHandler",
 *     "views_data" = "Drupal\aggregator\AggregatorItemViewsData"
 *   },
 *   uri_callback = "Drupal\aggregator\Entity\Item::buildUri",
 *   base_table = "aggregator_item",
 *   render_cache = FALSE,
 *   list_cache_tags = { "aggregator_feed_list" },
 *   entity_keys = {
 *     "id" = "iid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class Item extends ContentEntityBase implements ItemInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['iid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Aggregator item ID'))
      ->setDescription(t('The ID of the feed item.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['fid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source feed'))
      ->setRequired(TRUE)
      ->setDescription(t('The aggregator feed entity associated with this item.'))
      ->setSetting('target_type', 'aggregator_feed')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the feed item.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The feed item language code.'));

    $fields['link'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Link'))
      ->setDescription(t('The link of the feed item.'))
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['author'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Author'))
      ->setDescription(t('The author of the feed item.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The body of the feed item.'));

    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Posted on'))
      ->setDescription(t('Posted date of the feed item, as a Unix timestamp.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp_ago',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // @todo Convert to a real UUID field in
    //   https://www.drupal.org/node/2149851.
    $fields['guid'] = BaseFieldDefinition::create('string_long')
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
  public function getLink() {
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

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The Item entity has one special case: a newly
    // created Item is *also* associated with a Feed, so we must invalidate the
    // associated Feed's cache tag.
    if (!$update) {
      Cache::invalidateTags($this->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return Feed::load($this->getFeedId())->getCacheTags();
  }


  /**
   * Entity URI callback.
   */
  public static function buildUri(ItemInterface $item) {
    return Url::fromUri($item->getLink());
  }

}
