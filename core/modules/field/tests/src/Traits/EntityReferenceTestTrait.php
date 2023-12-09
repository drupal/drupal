<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Traits;

@trigger_error('The ' . __NAMESPACE__ . '\EntityReferenceTestTrait is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Instead, use \Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait. See https://www.drupal.org/node/3401941', E_USER_DEPRECATED);

/**
 * Provides common functionality for the EntityReference test classes.
 *
 * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
 *    \Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait instead.
 *
 * @see https://www.drupal.org/node/3401941
 */
trait EntityReferenceTestTrait {

  use EntityReferenceFieldCreationTrait;

}
