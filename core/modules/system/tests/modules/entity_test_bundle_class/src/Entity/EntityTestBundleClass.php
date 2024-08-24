<?php

declare(strict_types=1);

namespace Drupal\entity_test_bundle_class\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_test\Entity\EntityTest;

/**
 * The bundle class for the bundle_class bundle of the entity_test entity.
 */
class EntityTestBundleClass extends EntityTest {

  /**
   * The number of times static::preCreate() was called.
   *
   * @var int
   */
  public static $preCreateCount = 0;

  /**
   * The number of times static::postCreate() was called.
   *
   * This does not need to be static, since postCreate() is not static.
   *
   * @var int
   */
  public $postCreateCount = 0;

  /**
   * The number of times static::preDelete() was called.
   *
   * @var int
   */
  public static $preDeleteCount = 0;

  /**
   * The number of times static::postDelete() was called.
   *
   * @var int
   */
  public static $postDeleteCount = 0;

  /**
   * The number of times static::postLoad() was called.
   *
   * @var int
   */
  public static $postLoadCount = 0;

  /**
   * The size of the $entities array passed to each invocation of postLoad().
   *
   * @var int[]
   */
  public static $postLoadEntitiesCount = [];

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    self::$preCreateCount++;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);
    $this->postCreateCount++;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    self::$preDeleteCount++;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    self::$postDeleteCount++;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    self::$postLoadCount++;
    self::$postLoadEntitiesCount[] = count($entities);
  }

}
