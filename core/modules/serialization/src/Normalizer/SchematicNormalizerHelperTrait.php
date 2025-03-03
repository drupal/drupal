<?php

declare(strict_types=1);

namespace Drupal\serialization\Normalizer;

/**
 * Methods for normalizing JSON schema.
 */
trait SchematicNormalizerHelperTrait {

  use JsonSchemaReflectionTrait;

  /**
   * Retrieve JSON Schema for the normalization.
   *
   * @param mixed $object
   *   Supported object or class/interface name being normalized.
   * @param array $context
   *   Context options. Well-defined keys include:
   *   - dialect: Used to specify a dialect for the desired schema being
   *     generated. The dialect meta-schema MUST extend JSON Schema draft
   *     2020-12 or later. Normalizers MAY choose to return a schema with
   *     keywords supported by a dialect it supports, but only when they
   *     are supported by the dialect specified in this key. For instance,
   *     normalizers may return a schema with a 'discriminator' as supported
   *     by OpenAPI if that dialect is passed, but return a more permissive but
   *     less specific schema when it is not.
   *
   * @return array
   *   JSON Schema for the normalization, conforming to version draft 2020-12.
   *
   * @see https://json-schema.org/specification#specification-documents
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    return $this->getJsonSchemaForMethod($this, 'normalize', ['$comment' => 'No schema available.']);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    if ($format === 'json_schema') {
      return TRUE;
    }
    return parent::checkFormat($format);
  }

}
