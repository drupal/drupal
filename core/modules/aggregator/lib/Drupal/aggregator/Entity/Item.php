<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\Item.
 */

namespace Drupal\aggregator\Entity;

use Drupal\Core\Entity\EntityNG;
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
class Item extends EntityNG implements ItemInterface {

  /**
   * The feed item ID.
   *
   * @todo rename to id.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $iid;

  /**
   * The feed ID.
   *
   * @todo rename to feed_id.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $fid;

  /**
   * Title of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $title;

  /**
   * The feed language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * Link to the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $link;

  /**
   * Author of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $author;

  /**
   * Body of the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $description;

  /**
   * Posted date of the feed item, as a Unix timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $timestamp;

  /**
   * Unique identifier for the feed item.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $guid;

  /**
   * Overrides Drupal\Core\Entity\EntityNG::init().
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
    if (!isset($this->timestamp->value)) {
      $this->timestamp->value = REQUEST_TIME;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    $storage_controller->saveCategories($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    $storage_controller->deleteCategories($entities);
  }
}
