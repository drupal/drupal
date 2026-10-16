<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Condition\ConditionPluginBase.
 */
#[CoversClass(ConditionPluginBase::class)]
#[Group('Condition')]
class ConditionPluginBaseTest extends UnitTestCase {

  /**
   * Tests that the negate form value is cast to a boolean on validation.
   */
  #[DataProvider('providerTestValidateConfigurationForm')]
  public function testValidateConfigurationForm(mixed $value, bool $expected): void {
    $condition = new TestConditionPluginBase([], '', '');

    $form = [];
    $form_state = new FormState();
    $form_state->setValue('negate', $value);

    $condition->validateConfigurationForm($form, $form_state);

    $this->assertSame($expected, $form_state->getValue('negate'));
  }

  /**
   * Data provider for testValidateConfigurationForm().
   */
  public static function providerTestValidateConfigurationForm(): \Iterator {
    yield 'unchecked checkbox' => [0, FALSE];
    yield 'checked checkbox' => [1, TRUE];
    yield 'unchecked checkbox as string' => ['0', FALSE];
    yield 'checked checkbox as string' => ['1', TRUE];
    yield 'empty string' => ['', FALSE];
    yield 'boolean false' => [FALSE, FALSE];
    yield 'boolean true' => [TRUE, TRUE];
  }

}

/**
 * Concrete condition plugin for testing.
 */
class TestConditionPluginBase extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): string {
    return '';
  }

}
