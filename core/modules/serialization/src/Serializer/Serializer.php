<?php

declare(strict_types=1);

namespace Drupal\serialization\Serializer;

use Symfony\Component\Serializer\Serializer as SymfonySerializer;

/**
 * Serializer with JSON Schema generation convenience methods.
 */
class Serializer extends SymfonySerializer implements JsonSchemaProviderSerializerInterface {

  use JsonSchemaProviderSerializerTrait;

}
