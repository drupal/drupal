<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\TypedData\DataDefinitionInterface;

interface InputCollectorInterface {

  public function collectValue(string $name, DataDefinitionInterface $definition, mixed $default_value): mixed;

}
