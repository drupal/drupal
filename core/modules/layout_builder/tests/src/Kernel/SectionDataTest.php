<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Plugin\DataType\SectionData;
use Drupal\layout_builder\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\layout_builder\Plugin\DataType\SectionData.
 */
#[CoversClass(SectionData::class)]
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class SectionDataTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder'];

  /**
   * Tests set array value.
   *
   * @legacy-covers ::setValue
   */
  public function testSetArrayValue(): void {
    $definition = DataDefinition::create('layout_section');
    $data = $this->container->get(TypedDataManagerInterface::class)
      ->create($definition, name: 'test_section');

    // If an array is passed, it's converted to a Section object.
    $data->setValue([]);
    $this->assertInstanceOf(Section::class, $data->getValue());
    // Anything else should raise an exception.
    $this->expectExceptionMessage('Value assigned to "test_section" is not a valid section');
    $data->setValue('[]');
  }

}
