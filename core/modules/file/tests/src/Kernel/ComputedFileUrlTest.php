<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\file\FileInterface;
use Drupal\file\ComputedFileUrl;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\file\ComputedFileUrl
 *
 * @group file
 */
class ComputedFileUrlTest extends KernelTestBase {

  /**
   * The test URL to use.
   *
   * @var string
   */
  protected $testUrl = 'public://druplicon.txt';

  /**
   * @covers ::getValue
   */
  public function testGetValue() {
    $entity = $this->prophesize(FileInterface::class);
    $entity->getFileUri()
      ->willReturn($this->testUrl);

    $parent = $this->prophesize(FieldItemInterface::class);
    $parent->getEntity()
      ->shouldBeCalledTimes(2)
      ->willReturn($entity->reveal());

    $definition = $this->prophesize(DataDefinitionInterface::class);

    $typed_data = new ComputedFileUrl($definition->reveal(), $this->randomMachineName(), $parent->reveal());

    $expected = base_path() . $this->siteDirectory . '/files/druplicon.txt';

    $this->assertSame($expected, $typed_data->getValue());
    // Do this a second time to confirm the same value is returned but the value
    // isn't retrieved from the parent entity again.
    $this->assertSame($expected, $typed_data->getValue());
  }

  /**
   * @covers ::setValue
   */
  public function testSetValue() {
    $name = $this->randomMachineName();
    $parent = $this->prophesize(FieldItemInterface::class);
    $parent->onChange($name)
      ->shouldBeCalled();

    $definition = $this->prophesize(DataDefinitionInterface::class);
    $typed_data = new ComputedFileUrl($definition->reveal(), $name, $parent->reveal());

    // Setting the value explicitly should mean the parent entity is never
    // called into.
    $typed_data->setValue($this->testUrl);

    $this->assertSame($this->testUrl, $typed_data->getValue());
    // Do this a second time to confirm the same value is returned but the value
    // isn't retrieved from the parent entity again.
    $this->assertSame($this->testUrl, $typed_data->getValue());
  }

  /**
   * @covers ::setValue
   */
  public function testSetValueNoNotify() {
    $name = $this->randomMachineName();
    $parent = $this->prophesize(FieldItemInterface::class);
    $parent->onChange($name)
      ->shouldNotBeCalled();

    $definition = $this->prophesize(DataDefinitionInterface::class);
    $typed_data = new ComputedFileUrl($definition->reveal(), $name, $parent->reveal());

    // Setting the value should explicitly should mean the parent entity is
    // never called into.
    $typed_data->setValue($this->testUrl, FALSE);

    $this->assertSame($this->testUrl, $typed_data->getValue());
  }

}
