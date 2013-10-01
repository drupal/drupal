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
 *     "render" = "Drupal\aggregator\ItemRenderController"
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
   * The feed item ID.
   *
   * @todo rename to id.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $iid;

  /**
   * The feed ID.
   *
   * @todo rename to feed_id.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $fid;

  /**
   * Title of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $title;

  /**
   * The feed language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $langcode;

  /**
   * Link to the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $link;

  /**
   * Author of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $author;

  /**
   * Body of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $description;

  /**
   * Posted date of the feed item, as a Unix timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $timestamp;

  /**
   * Unique identifier for the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $guid;

  /**
   * {@inheritdoc}
   */
  public function init() {
    parent::init();

    // We unset all defined properties, so magic getters apply.
    unset($this->iid);
    unset($this->fid);
    unset($this->title);
    unset($this->author);
    unset($this->description);
    unset($this->guid);
    unset($this->link);
    unset($this->timestamp);
  }

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

    if (!isset($this->timestamp->value)) {
      $this->timestamp->value = REQUEST_TIME;
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

}
