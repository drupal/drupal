<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\file\ComputedFileUrl;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\file\ComputedFileUrl.
 */
#[CoversClass(ComputedFileUrl::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class ComputedFileUrlTest extends KernelTestBase {

  /**
   * The test URL to use.
   *
   * @var string
   */
  protected $testUrl = 'public://druplicon.txt';

  /**
   * Tests get value.
   *
   * @legacy-covers ::getValue
   */
  public function testGetValue(): void {
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
   * Tests set value.
   *
   * @legacy-covers ::setValue
   */
  public function testSetValue(): void {
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
   * Tests set value no notify.
   *
   * @legacy-covers ::setValue
   */
  public function testSetValueNoNotify(): void {
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
