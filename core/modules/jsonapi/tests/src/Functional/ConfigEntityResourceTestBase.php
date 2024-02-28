<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

/**
 * Resource test base class for config entities.
 *
 * @todo Remove this in https://www.drupal.org/node/2300677.
 */
abstract class ConfigEntityResourceTestBase extends ResourceTestBase {

  /**
   * A list of test methods to skip.
   *
   * @var array
   */
  const SKIP_METHODS = [
    'testRelated',
    'testRelationships',
    'testPostIndividual',
    'testPatchIndividual',
    'testDeleteIndividual',
    'testRevisions',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    if (in_array($this->name(), static::SKIP_METHODS, TRUE)) {
      // Skip before installing Drupal to prevent unnecessary use of resources.
      $this->markTestSkipped("Not yet supported for config entities.");
    }
    parent::setUp();
  }

}
