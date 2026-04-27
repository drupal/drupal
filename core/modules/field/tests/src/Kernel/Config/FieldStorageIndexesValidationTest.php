<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Config;

use Drupal\field\Plugin\Validation\Constraint\FieldStorageIndexesConstraint;
use Drupal\field\Plugin\Validation\Constraint\FieldStorageIndexesConstraintValidator;
use Drupal\entity_test\EntityTestHelper;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests validation for field storage indexes config.
 */
#[Group('field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(FieldStorageIndexesConstraint::class)]
#[CoversClass(FieldStorageIndexesConstraintValidator::class)]
class FieldStorageIndexesValidationTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'entity_test',
    'field_test',
    'field_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    EntityTestHelper::createBundle('test_bundle');
    $this->installConfig(['field_test_config']);
  }

  /**
   * Test valid indexes.
   */
  #[Test]
  public function testValidIndexes(): void {
    $config = \Drupal::configFactory()->getEditable('field.storage.entity_test.field_test_import');
    $typed_config_manager = \Drupal::service('config.typed');

    $valid_indexes = [
      ['value_format' => [['value', 127], ['format', 127]]],
      ['format' => ['value']],
      ['foo1' => ['value', 'format']],
      ['foo2' => ['value', 'format'], 'bar2' => ['value']],
      ['foo4' => ['value', 'format'], 'bar4' => ['value', ['format', 127]]],
      ['foo5' => []],
    ];

    foreach ($valid_indexes as $indexes) {
      $config->set('indexes', $indexes);
      $typed_config = $typed_config_manager->createFromNameAndData($config->getName(), $config->get());

      /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $errors */
      $errors = $typed_config->validate();
      $this->assertCount(0, $errors, 'Expected no validation violations: ' . $errors . '. Indexes: ' . print_r($indexes, TRUE));
    }
  }

  /**
   * Test invalid indexes.
   */
  #[Test]
  public function testInvalidIndexes(): void {
    $config = \Drupal::configFactory()->getEditable('field.storage.entity_test.field_test_import');
    $typed_config_manager = \Drupal::service('config.typed');

    $invalid_indexes = [
      ['' => ['value']],
      ['foo2' => ['']],
      ['foo3' => ['value' => 1]],
      ['foo4' => ['value' => 'value']],
      ['foo5' => 'value'],
      ['foo6' => [['value', 0]]],
      ['foo7' => [['value', -50]]],
      ['foo8' => [['value', '1']]],
      ['foo9' => [['value', 127, 2]]],
      ['foo10' => [[]]],
      ['foo11' => [['']]],
      ['foo12' => ['value', 127], 'bar12' => ['value', 127]],
      ['foo13' => [['value'], ['format']]],
      ['foo14' => ['value', 'format'], 'bar3' => ['value', ['format']]],
      [0 => ['value']],
    ];

    foreach ($invalid_indexes as $indexes) {
      $config->set('indexes', $indexes);
      $typed_config = $typed_config_manager->createFromNameAndData($config->getName(), $config->get());
      $this->assertNotCount(0, $typed_config->validate());
    }
  }

}
