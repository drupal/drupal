<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Config;

use Drupal\Core\Config\Schema\Sequence;
use Drupal\Core\Config\Schema\SequenceDataDefinition;
use Drupal\Core\Config\Schema\TypedConfigInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

// cspell:ignore nyans

/**
 * Tests config validation mechanism.
 *
 * @group Config
 */
class TypedConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = ['config_test.validation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('config_test');
  }

  /**
   * Verifies that the Typed Data API is implemented correctly.
   */
  public function testTypedDataAPI(): void {
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');

    // Test non-existent data.
    try {
      $typed_config_manager->get('config_test.non_existent');
      $this->fail('Expected error when trying to get non-existent typed config.');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertEquals('Missing required data for typed configuration: config_test.non_existent', $e->getMessage());
    }

    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');

    // Test a primitive.
    $string_data = $typed_config->get('llama');
    $this->assertInstanceOf(StringInterface::class, $string_data);
    $this->assertEquals('llama', $string_data->getValue());

    // Test complex data.
    $mapping = $typed_config->get('cat');
    /** @var \Drupal\Core\TypedData\ComplexDataInterface $mapping */
    $this->assertInstanceOf(ComplexDataInterface::class, $mapping);
    $this->assertInstanceOf(StringInterface::class, $mapping->get('type'));
    $this->assertEquals('kitten', $mapping->get('type')->getValue());
    $this->assertInstanceOf(IntegerInterface::class, $mapping->get('count'));
    $this->assertEquals(2, $mapping->get('count')->getValue());
    // Verify the item metadata is available.
    $this->assertInstanceOf(ComplexDataDefinitionInterface::class, $mapping->getDataDefinition());
    $this->assertArrayHasKey('type', $mapping->getProperties());
    $this->assertArrayHasKey('count', $mapping->getProperties());

    // Test accessing sequences.
    $sequence = $typed_config->get('giraffe');
    /** @var \Drupal\Core\TypedData\ListInterface $sequence */
    $this->assertInstanceOf(SequenceDataDefinition::class, $sequence->getDataDefinition());
    $this->assertSame(Sequence::class, $sequence->getDataDefinition()->getClass());
    $this->assertSame('sequence', $sequence->getDataDefinition()->getDataType());
    $this->assertInstanceOf(ComplexDataInterface::class, $sequence);
    $this->assertInstanceOf(StringInterface::class, $sequence->get('hum1'));
    $this->assertEquals('hum1', $sequence->get('hum1')->getValue());
    $this->assertEquals('hum2', $sequence->get('hum2')->getValue());
    $this->assertCount(2, $sequence->getIterator());
    // Verify the item metadata is available.
    $this->assertInstanceOf(SequenceDataDefinition::class, $sequence->getDataDefinition());

    // Test accessing typed config objects for simple config and config
    // entities.
    $typed_config_manager = \Drupal::service('config.typed');
    $typed_config = $typed_config_manager->createFromNameAndData('config_test.validation', \Drupal::configFactory()->get('config_test.validation')->get());
    $this->assertInstanceOf(TypedConfigInterface::class, $typed_config);
    $this->assertEquals(['_core', 'llama', 'cat', 'giraffe', 'uuid', 'string__not_blank', 'host'], array_keys($typed_config->getElements()));
    $this->assertSame('config_test.validation', $typed_config->getName());
    $this->assertSame('config_test.validation', $typed_config->getPropertyPath());
    $this->assertSame('config_test.validation.llama', $typed_config->get('llama')->getPropertyPath());

    $config_test_entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => 'test',
      'label' => 'Test',
      'weight' => 11,
      'style' => 'test_style',
    ]);

    $typed_config = $typed_config_manager->createFromNameAndData($config_test_entity->getConfigDependencyName(), $config_test_entity->toArray());
    $this->assertInstanceOf(TypedConfigInterface::class, $typed_config);
    $this->assertEquals(['uuid', 'langcode', 'status', 'dependencies', 'id', 'label', 'weight', 'style', 'size', 'size_value', 'protected_property'], array_keys($typed_config->getElements()));
  }

  /**
   * Tests the behavior of `NotBlank` on required data.
   *
   * @testWith ["", false, "This value should not be blank."]
   *           ["", true, "This value should not be blank."]
   *           [null, false, "This value should not be blank."]
   *           [null, true, "This value should not be null."]
   *
   * @see \Drupal\Core\TypedData\DataDefinition::getConstraints()
   * @see \Drupal\Core\TypedData\DataDefinitionInterface::isRequired()
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint
   * @see \Symfony\Component\Validator\Constraints\NotBlank::$allowNull
   */
  public function testNotBlankInteractionWithNotNull(?string $value, bool $is_required, string $expected_message): void {
    \Drupal::configFactory()->getEditable('config_test.validation')
      ->set('string__not_blank', $value)
      ->save();

    $typed_config = \Drupal::service('config.typed')->get('config_test.validation');
    $typed_config->get('string__not_blank')->getDataDefinition()->setRequired($is_required);
    $result = $typed_config->validate();

    // Expect 1 validation error message: the one from `NotBlank` or `NotNull`.
    $this->assertCount(1, $result);
    $this->assertSame('string__not_blank', $result->get(0)->getPropertyPath());
    $this->assertEquals($expected_message, $result->get(0)->getMessage());
  }

  /**
   * Tests config validation via the Typed Data API.
   */
  public function testSimpleConfigValidation(): void {
    $config = \Drupal::configFactory()->getEditable('config_test.validation');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');

    $result = $typed_config->validate();
    $this->assertInstanceOf(ConstraintViolationListInterface::class, $result);
    $this->assertEmpty($result);

    // Test constraints on primitive types.
    $config->set('llama', 'elephant');
    $config->save();

    $typed_config = $typed_config_manager->get('config_test.validation');
    $result = $typed_config->validate();
    // Its not a valid llama anymore.
    $this->assertCount(1, $result);
    $this->assertEquals('no valid llama', $result->get(0)->getMessage());

    // Test constraints on mapping.
    $config->set('llama', 'llama');
    $config->set('cat.type', 'nyans');
    $config->save();

    $typed_config = $typed_config_manager->get('config_test.validation');
    $result = $typed_config->validate();
    $this->assertEmpty($result);

    // Test constrains on nested mapping.
    $config->set('cat.type', 'tiger');
    $config->save();

    $typed_config = $typed_config_manager->get('config_test.validation');
    $result = $typed_config->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('no valid cat', $result->get(0)->getMessage());

    // Test constrains on sequences elements.
    $config->set('cat.type', 'nyans');
    $config->set('giraffe', ['muh', 'hum2']);
    $config->save();
    $typed_config = $typed_config_manager->get('config_test.validation');
    $result = $typed_config->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('Giraffes just hum', $result->get(0)->getMessage());

    // Test constrains on the sequence itself.
    $config->set('giraffe', ['hum', 'hum2', 'invalid-key' => 'hum']);
    $config->save();

    $typed_config = $typed_config_manager->get('config_test.validation');
    $result = $typed_config->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('giraffe', $result->get(0)->getPropertyPath());
    $this->assertEquals('Invalid giraffe key.', $result->get(0)->getMessage());

    // Validates mapping.
    $typed_config = $typed_config_manager->get('config_test.validation');
    $value = $typed_config->getValue();
    unset($value['giraffe']);
    $value['elephant'] = 'foo';
    $value['zebra'] = 'foo';
    $typed_config->setValue($value);
    $result = $typed_config->validate();
    $this->assertCount(3, $result);
    // 2 constraint violations triggered by the default validation constraint
    // for `type: mapping`
    // @see \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint
    $this->assertSame('elephant', $result->get(0)->getPropertyPath());
    $this->assertEquals("'elephant' is not a supported key.", $result->get(0)->getMessage());
    $this->assertSame('zebra', $result->get(1)->getPropertyPath());
    $this->assertEquals("'zebra' is not a supported key.", $result->get(1)->getMessage());
    // 1 additional constraint violation triggered by the custom
    // constraint for the `config_test.validation` type, which indirectly
    // extends `type: mapping` (via `type: config_object`).
    // @see \Drupal\config_test\ConfigValidation::validateMapping()
    $this->assertEquals('', $result->get(2)->getPropertyPath());
    $this->assertEquals('Unexpected keys: elephant, zebra', $result->get(2)->getMessage());
  }

}
