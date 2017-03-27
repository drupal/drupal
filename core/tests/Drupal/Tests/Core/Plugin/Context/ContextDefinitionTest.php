<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ContextDefinition class.
 *
 * @group Plugin
 *
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextDefinition
 */
class ContextDefinitionTest extends UnitTestCase {

  /**
   * Very simple data provider.
   */
  public function providerGetDataDefinition() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

  /**
   * @dataProvider providerGetDataDefinition
   * @covers ::getDataDefinition
   * @uses \Drupal
   */
  public function testGetDataDefinition($is_multiple) {
    $data_type = 'valid';
    $mock_data_definition = $this->getMockBuilder('\Drupal\Core\TypedData\ListDataDefinitionInterface')
      ->setMethods([
        'setLabel',
        'setDescription',
        'setRequired',
        'getConstraints',
        'setConstraints',
      ])
      ->getMockForAbstractClass();
    $mock_data_definition->expects($this->once())
      ->method('setLabel')
      ->willReturnSelf();
    $mock_data_definition->expects($this->once())
      ->method('setDescription')
      ->willReturnSelf();
    $mock_data_definition->expects($this->once())
      ->method('setRequired')
      ->willReturnSelf();
    $mock_data_definition->expects($this->once())
      ->method('getConstraints')
      ->willReturn([]);
    $mock_data_definition->expects($this->once())
      ->method('setConstraints')
      ->willReturn(NULL);

    // Follow code paths for both multiple and non-multiple definitions.
    $create_definition_method = 'createDataDefinition';
    if ($is_multiple) {
      $create_definition_method = 'createListDataDefinition';
    }
    $mock_data_manager = $this->getMock(TypedDataManagerInterface::class);
    // Our mocked data manager will return our mocked data definition for a
    // valid data type.
    $mock_data_manager->expects($this->once())
      ->method($create_definition_method)
      ->willReturnMap([
        ['not_valid', NULL],
        ['valid', $mock_data_definition],
      ]);

    // Mock a ContextDefinition object, setting up expectations for many of the
    // methods.
    $mock_context_definition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinition')
      ->disableOriginalConstructor()
      ->setMethods([
        'isMultiple',
        'getTypedDataManager',
        'getDataType',
        'getLabel',
        'getDescription',
        'isRequired',
        'getConstraints',
        'setConstraints',
      ])
      ->getMock();
    $mock_context_definition->expects($this->once())
      ->method('isMultiple')
      ->willReturn($is_multiple);
    $mock_context_definition->expects($this->once())
      ->method('getTypedDataManager')
      ->willReturn($mock_data_manager);
    $mock_context_definition->expects($this->once())
      ->method('getDataType')
      ->willReturn($data_type);
    $mock_context_definition->expects($this->once())
      ->method('getConstraints')
      ->willReturn([]);

    $this->assertSame(
      $mock_data_definition,
      $mock_context_definition->getDataDefinition()
    );
  }

  /**
   * @dataProvider providerGetDataDefinition
   * @covers ::getDataDefinition
   * @uses \Drupal
   * @uses \Drupal\Component\Utility\SafeMarkup
   */
  public function testGetDataDefinitionInvalidType($is_multiple) {
    // Since we're trying to make getDataDefinition() throw an exception in
    // isolation, we use a data type which is not valid.
    $data_type = 'not_valid';
    $mock_data_definition = $this->getMockBuilder('\Drupal\Core\TypedData\ListDataDefinitionInterface')
      ->getMockForAbstractClass();

    // Follow code paths for both multiple and non-multiple definitions.
    $create_definition_method = 'createDataDefinition';
    if ($is_multiple) {
      $create_definition_method = 'createListDataDefinition';
    }
    $mock_data_manager = $this->getMock(TypedDataManagerInterface::class);
    // Our mocked data manager will return NULL for a non-valid data type. This
    // will eventually cause getDataDefinition() to throw an exception.
    $mock_data_manager->expects($this->once())
      ->method($create_definition_method)
      ->willReturnMap([
        ['not_valid', NULL],
        ['valid', $mock_data_definition],
      ]);

    // Mock a ContextDefinition object with expectations for only the methods
    // that will be called before the expected exception.
    $mock_context_definition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinition')
      ->disableOriginalConstructor()
      ->setMethods([
        'isMultiple',
        'getTypedDataManager',
        'getDataType',
      ])
      ->getMock();
    $mock_context_definition->expects($this->once())
      ->method('isMultiple')
      ->willReturn($is_multiple);
    $mock_context_definition->expects($this->once())
      ->method('getTypedDataManager')
      ->willReturn($mock_data_manager);
    $mock_context_definition
      ->method('getDataType')
      ->willReturn($data_type);

    $this->setExpectedException(\Exception::class);
    $mock_context_definition->getDataDefinition();
  }

  /**
   * Data provider for testGetConstraint
   */
  public function providerGetConstraint() {
    return [
      [NULL, [], 'nonexistent_constraint_name'],
      [
        'not_null',
        [
          'constraint_name' => 'not_null',
        ],
        'constraint_name',
      ],
    ];
  }

  /**
   * @dataProvider providerGetConstraint
   * @covers ::getConstraint
   * @uses \Drupal
   */
  public function testGetConstraint($expected, $constraint_array, $constraint) {
    $mock_context_definition = $this->getMockBuilder('Drupal\Core\Plugin\Context\ContextDefinition')
      ->disableOriginalConstructor()
      ->setMethods([
        'getConstraints',
      ])
      ->getMock();
    $mock_context_definition->expects($this->once())
      ->method('getConstraints')
      ->willReturn($constraint_array);

    $this->assertEquals($expected, $mock_context_definition->getConstraint($constraint));
  }

  /**
   * @covers ::getDefaultValue
   * @covers ::setDefaultValue
   */
  public function testDefaultValue() {
    $context_definition = new ContextDefinition();
    $this->assertNull($context_definition->getDefaultValue());
    $context_definition->setDefaultValue('test');
    $this->assertSame('test', $context_definition->getDefaultValue());
  }

}
