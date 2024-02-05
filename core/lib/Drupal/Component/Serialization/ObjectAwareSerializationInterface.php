<?php

namespace Drupal\Component\Serialization;

// cspell:ignore serializers igbinary

/**
 * Ensures that a serializer is usable for serializing PHP objects.
 *
 * Other Serializers that implement the SerializationInterface, for example
 * serializers that use JSON or YAML, are suitable for different PHP types
 * except objects. Serializers that implement the
 * ObjectAwareSerializationInterface instead are clearly indicating that they're
 * suitable for PHP objects, for example using the PHP string serialization
 * format or the igbinary format.
 */
interface ObjectAwareSerializationInterface extends SerializationInterface {
}
