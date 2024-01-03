<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines a test interface to mock entity base field definitions.
 */
interface TestBaseFieldDefinitionInterface extends FieldDefinitionInterface, FieldStorageDefinitionInterface {
}
