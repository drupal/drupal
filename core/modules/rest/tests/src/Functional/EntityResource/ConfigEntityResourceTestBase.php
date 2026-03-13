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
   * A list of test methods to skip.
   *
   * @var array
   */
  const SKIP_METHODS = ['testPost', 'testPatch', 'testDelete'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (in_array($this->name(), static::SKIP_METHODS, TRUE)) {
      // Skip before installing Drupal to prevent unnecessary use of resources.
      $this->markTestSkipped("Not yet supported for config entities.");
    }
    parent::setUp();
  }

}
