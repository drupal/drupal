<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Image;

use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * GD image toolkit image manipulation of AVIF images.
 */
#[CoversClass(GDToolkit::class)]
#[Group('Image')]
#[RequiresPhpExtension('gd')]
#[RunTestsInSeparateProcesses]
class GdToolkitAvifImageManipulationTest extends GdToolkitImageManipulationTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $sourceTestImage = 'img-test.avif';

}
