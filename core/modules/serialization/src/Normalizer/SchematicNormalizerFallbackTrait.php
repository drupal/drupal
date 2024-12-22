<?php

declare(strict_types=1);

namespace Drupal\serialization\Normalizer;

/**
 * Trait for generating helpful schema-generation fallback messages.
 */
trait SchematicNormalizerFallbackTrait {

  public static function generateNoSchemaAvailableMessage(mixed $object): string {
    $baseMessage = 'See https://www.drupal.org/node/3424710 for information on implementing schemas in your program code.';
    return is_object($object)
      ? sprintf('No schema is defined for property of type %s. %s', $object::class, $baseMessage)
      : sprintf('No schema defined for this property. %s', $baseMessage);
  }

}
