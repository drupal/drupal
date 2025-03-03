<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Traits;

use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;
use JsonSchema\Validator;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Trait for testing JSON Schema validity and fit to sample data.
 *
 * In most cases, tests need only implement the abstract method for providing
 * a full set of representative normalized values.
 */
trait JsonSchemaTestTrait {

  /**
   * Format that should be used when performing test normalizations.
   */
  protected function getJsonSchemaTestNormalizationFormat(): ?string {
    return NULL;
  }

  /**
   * Data provider for ::testNormalizedValuesAgainstJsonSchema.
   *
   * @return array
   *   Array of possible normalized values to validate the JSON schema against.
   */
  abstract public static function jsonSchemaDataProvider(): array;

  /**
   * Method to make prophecy public for use in data provider closures.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<object>
   *   A new prophecy object.
   */
  public function doProphesize(?string $classOrInterface = NULL): ObjectProphecy {
    return $this->prophesize($classOrInterface);
  }

  /**
   * Test that a valid schema is returned for the explicitly supported types.
   *
   * This is in many cases an interface, which would not be normalized directly,
   * however the schema should never return an invalid type. An empty array or
   * a type with only a '$comment' member is valid.
   *
   * @dataProvider supportedTypesDataProvider
   */
  public function testSupportedTypesSchemaIsValid(string $type): void {
    $this->doTestJsonSchemaIsValid($type, TRUE);
  }

  /**
   * Check a schema is valid against the meta-schema.
   *
   * @param array $defined_schema
   *   Defined schema.
   * @param bool $accept_no_schema_type
   *   Whether to accept a schema with no meaningful type construct.
   */
  protected function doCheckSchemaAgainstMetaSchema(array $defined_schema, bool $accept_no_schema_type = FALSE): void {
    $validator = $this->getValidator();
    // Ensure the schema contains a meaningful type construct.
    if (!$accept_no_schema_type) {
      $this->assertFalse(empty(array_filter(array_keys($defined_schema), fn($key) => in_array($key, ['type', 'allOf', 'oneOf', 'anyOf', 'not', '$ref']))));
    }
    // All associative arrays must be encoded as objects.
    $schema = json_decode(json_encode($defined_schema));
    $validator->validate(
      $schema,
      // Schemas must be compatible with draft 2020-12, however the validation
      // library, justinrainbow/json-schema, only supports up to draft-04.
      // Generally speaking this isn't an issue as there are few changes to the
      // spec that will affect core-provided normalization schemas, and we have
      // little other option in the PHP ecosystem for runtime validation.
      // @see https://www.drupal.org/project/drupal/issues/3350943
      (object) ['$ref' => 'file://' . __DIR__ . '/../../../src/json-schema-draft-04-meta-schema.json']
    );
    $this->assertTrue($validator->isValid());
  }

  /**
   * Validate the normalizer's JSON schema.
   *
   * @param mixed $type
   *   Object/type being normalized.
   * @param bool $accept_no_schema_type
   *   Whether to accept a schema with no meaningful type.
   *
   * @return array
   *   Schema, so later tests can avoid retrieving it again.
   */
  public function doTestJsonSchemaIsValid(mixed $type, bool $accept_no_schema_type = FALSE): array {
    $defined_schema = $this->getNormalizer()->normalize($type, 'json_schema');
    $this->doCheckSchemaAgainstMetaSchema($defined_schema, $accept_no_schema_type);
    return $defined_schema;
  }

  /**
   * @return array
   *   Supported types for which to test schema generation.
   */
  public static function supportedTypesDataProvider(): array {
    return array_map(fn ($type) => [$type], array_keys((new PrimitiveDataNormalizer())->getSupportedTypes(NULL)));
  }

  /**
   * Test normalized values against the JSON schema.
   *
   * @dataProvider jsonSchemaDataProvider
   */
  public function testNormalizedValuesAgainstJsonSchema(mixed $value): void {
    // Explicitly test the JSON Schema's validity here, because it will depend
    // on the type of the data being normalized, e.g. a class implementing the
    // interface defined in ::getSupportedTypes().
    if ($value instanceof \Closure) {
      $value = $value($this);
    }
    $schema = $this->doTestJsonSchemaIsValid($value);
    $validator = $this->getValidator();
    // Test the value validates to the schema.
    // All associative arrays must be encoded as objects.
    $normalized = json_decode(json_encode($this->getNormalizationForValue($value)));
    $validator->validate($normalized, $schema);
    $this->assertSame([], $validator->getErrors(), 'Validation errors on object ' . print_r($normalized, TRUE) . ' with schema ' . print_r($schema, TRUE));
  }

  /**
   * Helper method to retrieve the normalizer.
   *
   * Override this method if the normalizer has a custom getter or is not
   * already present at $this->normalizer.
   *
   * @return \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   *   The normalizer under test.
   */
  protected function getNormalizer(): NormalizerInterface {
    return $this->normalizer;
  }

  /**
   * Get the normalization for a value.
   *
   * Override this method if the normalization needs further processing, e.g.
   * in the case of JSON:API module's CacheableDependencyInterface.
   *
   * @param mixed $value
   *   Value to be normalized.
   *
   * @return mixed
   *   Final normalized value.
   */
  protected function getNormalizationForValue(mixed $value): mixed {
    return $this->getNormalizer()->normalize($value, $this->getJsonSchemaTestNormalizationFormat());
  }

  /**
   * Get the JSON Schema Validator.
   *
   * Override this method to add additional schema translations to the loader.
   *
   * @return \JsonSchema\Validator
   *   Schema validator.
   */
  protected function getValidator(): Validator {
    return new Validator();
  }

}
