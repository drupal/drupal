<?php

/*
 * @file
 * Definition of Drupal\field_test\TestEntity.
 */

namespace Drupal\field_test;

use Drupal\entity\Entity;

/**
 * Test entity class.
 */
class TestEntity extends Entity {

  /**
   * Primary key.
   *
   * @var integer
   */
  public $ftid;

  /**
   * Revision key.
   *
   * @var integer
   */
  public $ftvid;

  /**
   * Bundle
   *
   * @var string
   */
  public $fttype;

  /**
   * Overrides Drupal\entity\Entity::id().
   */
  public function id() {
    return $this->ftid;
  }

  /**
   * Overrides Drupal\entity\Entity::getRevisionId().
   */
  public function getRevisionId() {
    return $this->ftvid;
  }

  /**
   * Overrides Drupal\entity\Entity::bundle().
   */
  public function bundle() {
    return !empty($this->fttype) ? $this->fttype : $this->entityType();
  }
}

