<?php

namespace Drupal\Tests\Component\Plugin\Context;

use Drupal\Component\Plugin\Context\Context;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Context\Context
 * @group Plugin
 */
class ContextTest extends UnitTestCase {

  /**
   * Data provider for testGetContextValue.
   */
  public function providerGetContextValue() {
    return [
      ['context_value', 'context_value', FALSE, 'data_type'],
      [NULL, NULL, FALSE, 'data_type'],
      ['will throw exception', NULL, TRUE, 'data_type'],
    ];
  }

  /**
   * @covers ::getContextValue
   * @dataProvider providerGetContextValue
   */
  public function testGetContextValue($expected, $context_value, $is_required, $data_type) {
    // Mock a Context object.
    $mock_context = $this->getMockBuilder('Drupal\Component\Plugin\Context\Context')
      ->disableOriginalConstructor()
      ->setMethods(array('getContextDefinition'))
      ->getMock();

    // If the context value exists, getContextValue() behaves like a normal
    // getter.
    if ($context_value) {
      // Set visibility of contextValue.
      $ref_context_value = new \ReflectionProperty($mock_context, 'contextValue');
      $ref_context_value->setAccessible(TRUE);
      // Set contextValue to a testable state.
      $ref_context_value->setValue($mock_context, $context_value);
      // Exercise getContextValue().
      $this->assertEquals($context_value, $mock_context->getContextValue());
    }
    // If no context value exists, we have to cover either returning NULL or
    // throwing an exception if the definition requires it.
    else {
      // Create a mock definition.
      $mock_definition = $this->getMockBuilder('Drupal\Component\Plugin\Context\ContextDefinitionInterface')
        ->setMethods(array('isRequired', 'getDataType'))
        ->getMockForAbstractClass();

      // Set expectation for isRequired().
      $mock_definition->expects($this->once())
        ->method('isRequired')
        ->willReturn($is_required);

      // Set expectation for getDataType().
      $mock_definition->expects($this->exactly(
            $is_required ? 1 : 0
        ))
        ->method('getDataType')
        ->willReturn($data_type);

      // Set expectation for getContextDefinition().
      $mock_context->expects($this->once())
        ->method('getContextDefinition')
        ->willReturn($mock_definition);

      // Set expectation for exception.
      if ($is_required) {
        $this->setExpectedException(
          'Drupal\Component\Plugin\Exception\ContextException',
          sprintf("The %s context is required and not present.", $data_type)
        );
      }

      // Exercise getContextValue().
      $this->assertEquals($context_value, $mock_context->getContextValue());
    }
  }

  /**
   * @covers ::getContextValue
   */
  public function testDefaultValue() {
    $mock_definition = $this->getMockBuilder('Drupal\Component\Plugin\Context\ContextDefinitionInterface')
      ->setMethods(array('getDefaultValue'))
      ->getMockForAbstractClass();

    $mock_definition->expects($this->once())
      ->method('getDefaultValue')
      ->willReturn('test');

    $context = new Context($mock_definition);
    $this->assertEquals('test', $context->getContextValue());
  }

}
