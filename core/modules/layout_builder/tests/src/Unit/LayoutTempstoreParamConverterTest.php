<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Routing\LayoutTempstoreParamConverter;
use Drupal\layout_builder\Routing\SectionStorageParamConverterInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\LayoutTempstoreParamConverter
 *
 * @group layout_builder
 */
class LayoutTempstoreParamConverterTest extends UnitTestCase {

  /**
   * @covers ::convert
   * @covers ::getParamConverterFromDefaults
   */
  public function testConvert() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $param_converter = $this->prophesize(SectionStorageParamConverterInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $class_resolver->reveal());

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => 'my_type'];
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $expected = 'the_return_value';

    $class_resolver->getInstanceFromDefinition('layout_builder.section_storage_param_converter.my_type')->willReturn($param_converter->reveal());
    $param_converter->convert($value, $definition, $name, $defaults)->willReturn($section_storage->reveal());
    $layout_tempstore_repository->get($section_storage->reveal())->willReturn($expected);

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
   * @covers ::getParamConverterFromDefaults
   */
  public function testConvertNoType() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $class_resolver->reveal());

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => NULL];

    $class_resolver->getInstanceFromDefinition()->shouldNotBeCalled();
    $layout_tempstore_repository->get()->shouldNotBeCalled();

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertNull($result);
  }

  /**
   * @covers ::convert
   * @covers ::getParamConverterFromDefaults
   */
  public function testConvertInvalidConverter() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $class_resolver->reveal());

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => 'invalid'];

    $class_resolver->getInstanceFromDefinition('layout_builder.section_storage_param_converter.invalid')->willThrow(\InvalidArgumentException::class);
    $layout_tempstore_repository->get()->shouldNotBeCalled();

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertNull($result);
  }

}
