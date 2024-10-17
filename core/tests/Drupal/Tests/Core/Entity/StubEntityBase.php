<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityBase;

/**
 * A stub base entity for testing purposes.
 */
class StubEntityBase extends EntityBase {

  public $id;
  public $langcode;
  public $uuid;
  public $label;

}
