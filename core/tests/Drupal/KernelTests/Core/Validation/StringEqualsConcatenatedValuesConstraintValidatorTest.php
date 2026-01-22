<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraintValidator;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the StringEqualsConcatenatedValues validator.
 */
#[Group('Validation')]
#[CoversClass(StringEqualsConcatenatedValuesConstraint::class)]
#[CoversClass(StringEqualsConcatenatedValuesConstraintValidator::class)]
#[RunTestsInSeparateProcesses]
class StringEqualsConcatenatedValuesConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('config_test');
  }

  /**
   * Tests basic validation of concatenated config values.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraint
   */
  public function testStringEqualsConcatenatedValuesConstraint(): void {
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');

    // Test valid names.
    $typed_config->get('string_concat_values')->setValue('localhost.llama');
    $this->assertCount(0, $typed_config->validate());

    // Test invalid names.
    $typed_config->get('string_concat_values')->setValue('drupal.kitten');

    $constraintViolationList = $typed_config->validate();
    $this->assertCount(1, $constraintViolationList);

    $this->assertSame("Expected 'localhost.llama', not 'drupal.kitten'. Format: '&lt;%parent.string_concat_value_1&gt;.&lt;%parent.string_concat_value_2&gt;'.", (string) $constraintViolationList->get(0)->getMessage());
  }

  /**
   * Tests that validation fails if the schema references missing properties.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraint
   */
  public function testInvalidReferenceToProperties(): void {
    $this->expectExceptionMessage('Schema errors for config_test.validation with the following errors: 0 [string_concat_values_invalid] This validation constraint is configured to inspect the properties &lt;em class=&quot;placeholder&quot;&gt;%parent.invalid, %parent.reference&lt;/em&gt;, but some do not exist: &lt;em class=&quot;placeholder&quot;&gt;%parent.invalid, %parent.reference&lt;/em&gt;.');

    /** @var \Drupal\Core\Config\Config $editable_config */
    $editable_config = \Drupal::configFactory()->getEditable('config_test.validation');
    $editable_config->set('string_concat_values_invalid', 'test');
    $editable_config->save();
  }

  /**
   * Tests that validation fails if reserved characters are not replaced.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraint
   */
  public function testReservedCharacters(): void {
    /** @var \Drupal\Core\Config\Config $editable_config */
    $editable_config = \Drupal::configFactory()->getEditable('config_test.validation');
    $editable_config->set('string_concat_value_1', 'test|||value');
    $editable_config->set('string_concat_values', 'test>>>value.llama');
    $editable_config->save();

    $this->expectExceptionMessage('Schema errors for config_test.validation with the following errors: 0 [string_concat_values] Expected &#039;test&amp;gt;&amp;gt;&amp;gt;value.llama&#039;, not &#039;test|||value.llama&#039;. Format: &#039;&amp;lt;%parent.string_concat_value_1&amp;gt;.&amp;lt;%parent.string_concat_value_2&amp;gt;&#039;.');

    $editable_config->set('string_concat_values', 'test|||value.llama');
    $editable_config->save();
  }

  /**
   * Tests concatenation of basic schema value types.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\StringEqualsConcatenatedValuesConstraint
   */
  #[DataProvider('valueTypesProvider')]
  public function testValueTypes($first_value, $second_value, $result, $invalid_result): void {
    $editable_config = \Drupal::configFactory()->getEditable('config_test.validation');
    $editable_config->set('string_concat_value_1', $first_value);
    $editable_config->set('string_concat_value_2', $second_value);
    $editable_config->set('string_concat_values', $result);
    $editable_config->save();

    $this->expectExceptionMessage("Schema errors for config_test.validation with the following errors: 0 [string_concat_values] Expected &#039;$result&#039;, not &#039;$invalid_result&#039;. Format: &#039;&amp;lt;%parent.string_concat_value_1&amp;gt;.&amp;lt;%parent.string_concat_value_2&amp;gt;&#039;");
    $editable_config->set('string_concat_values', $invalid_result);
    $editable_config->save();
  }

  /**
   * Data provider for testValueTypes().
   */
  public static function valueTypesProvider(): array {
    return [
      ['part1', 'part2', 'part1.part2', 'part1-part2'],
      [123, 456, '123.456', '123-456'],
      [TRUE, FALSE, '1.', '1-0'],
    ];
  }

}
