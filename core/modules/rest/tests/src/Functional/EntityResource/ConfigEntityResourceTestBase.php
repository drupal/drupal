<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Functional\EntityResource;

/**
 * Resource test base class for config entities.
 *
 * @todo Remove this in https://www.drupal.org/node/2300677.
 */
abstract class ConfigEntityResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public function testCrud(): void {
    $this->doTestGet();
  }

}
