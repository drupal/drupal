<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityHandlerBase;

/**
 * A stub base entity handler for testing purposes.
 */
class StubEntityHandlerBase extends EntityHandlerBase {

  /**
   * {@inheritdoc}
   */
  public $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public $stringTranslation;

}
