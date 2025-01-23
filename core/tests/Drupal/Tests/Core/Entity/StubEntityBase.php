<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityBase;

/**
 * A stub base entity for testing purposes.
 */
class StubEntityBase extends EntityBase {

  /**
   * The ID for the stub entity.
   */
  public $id;

  /**
   * The language code for the stub entity.
   */

  public $langcode;

  /**
   * The UUID for the stub entity.
   */
  public $uuid;

  /**
   * The label for the stub entity.
   */
  public $label;

}
