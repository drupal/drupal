<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Routing\LayoutTempstoreParamConverter;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
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
   */
  public function testConvert() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $section_storage_manager->reveal());

    $section_storage = $this->prophesize(SectionStorageInterface::class);

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => 'my_type'];
    $expected = 'the_return_value';

    $section_storage_manager->hasDefinition('my_type')->willReturn(TRUE);
    $section_storage_manager->loadEmpty('my_type')->willReturn($section_storage->reveal());
    $section_storage->deriveContextsFromRoute($value, $definition, $name, $defaults)->willReturn([]);
    $section_storage_manager->load('my_type', [])->willReturn($section_storage->reveal());

    $layout_tempstore_repository->get($section_storage->reveal())->willReturn($expected);

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertNoType() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $section_storage_manager->reveal());

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => NULL];

    $section_storage_manager->hasDefinition()->shouldNotBeCalled();
    $section_storage_manager->load()->shouldNotBeCalled();
    $layout_tempstore_repository->get()->shouldNotBeCalled();

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertNull($result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertInvalidConverter() {
    $layout_tempstore_repository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $converter = new LayoutTempstoreParamConverter($layout_tempstore_repository->reveal(), $section_storage_manager->reveal());

    $value = 'some_value';
    $definition = ['layout_builder_tempstore' => TRUE];
    $name = 'the_parameter_name';
    $defaults = ['section_storage_type' => 'invalid'];

    $section_storage_manager->hasDefinition('invalid')->willReturn(FALSE);
    $section_storage_manager->load()->shouldNotBeCalled();
    $layout_tempstore_repository->get()->shouldNotBeCalled();

    $result = $converter->convert($value, $definition, $name, $defaults);
    $this->assertNull($result);
  }

}
