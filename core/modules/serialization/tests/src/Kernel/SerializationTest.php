<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Kernel;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Functional tests for serialization system.
 */
#[Group('serialization')]
#[RunTestsInSeparateProcesses]
class SerializationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'serialization_test'];

  /**
   * The serializer service to test.
   *
   * @var \Drupal\serialization\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Confirms that modules can register normalizers and encoders.
   */
  public function testSerializerComponentRegistration(): void {
    $object = new \stdClass();
    $format = 'serialization_test';
    $expected = 'Normalized by SerializationTestNormalizer, Encoded by SerializationTestEncoder';

    // Ensure the serialization invokes the expected normalizer and encoder.
    $this->assertSame($expected, $this->serializer->serialize($object, $format));

    // Ensure the serialization fails for an unsupported format.
    try {
      $this->serializer->serialize($object, 'unsupported_format');
      $this->fail('The serializer was expected to throw an exception for an unsupported format, but did not.');
    }
    catch (UnexpectedValueException) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests that getJsonSchema() returns the schema of an object.
   *
   * A required string is not nullable, see
   * \Drupal\serialization\Normalizer\PrimitiveDataNormalizer::getNormalizationSchema().
   */
  public function testGetJsonSchema(): void {
    $definition = DataDefinition::create('string')->setRequired(TRUE);
    $data = $this->container->get('typed_data_manager')->create($definition);
    $this->assertSame(['type' => 'string'], $this->serializer->getJsonSchema($data, []));
  }

  /**
   * Tests that passing an array to getJsonSchema() is deprecated.
   */
  #[IgnoreDeprecations]
  public function testGetJsonSchemaArrayDeprecation(): void {
    $data = $this->container->get('typed_data_manager')->create(DataDefinition::create('string'));
    $schema = $this->serializer->getJsonSchema($data, []);

    $this->expectUserDeprecationMessage('Passing anything other than an object to the $object parameter in Drupal\serialization\Serializer\Serializer::getJsonSchema() is deprecated in drupal:12.1.0 and is removed from drupal:13.0.0. Pass one object per call instead. See https://www.drupal.org/node/3632615');
    // @phpstan-ignore argument.type
    $this->assertSame([$schema], $this->serializer->getJsonSchema([$data], []));
  }

}
