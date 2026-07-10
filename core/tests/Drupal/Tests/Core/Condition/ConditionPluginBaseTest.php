<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Condition\ConditionPluginBase.
 */
#[CoversClass(ConditionPluginBase::class)]
#[Group('Condition')]
class ConditionPluginBaseTest extends UnitTestCase {

  /**
   * Tests the validateConfigurationForm() method.
   */
  public function testValidateConfigurationForm(): void {
    $form_validator = $this->getMockBuilder(ConditionPluginBase::class)
      ->setConstructorArgs([[], '', ''])
      ->onlyMethods(['evaluate', 'summary'])
      ->getMock();

    $form = [];
    $form_state = new FormState();

    $form_state->setValue('negate', 0);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', 1);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', FALSE);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', TRUE);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));
  }

}
