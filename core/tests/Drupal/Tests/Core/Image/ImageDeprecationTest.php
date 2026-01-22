<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Image;

use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Drupal\system\Plugin\ImageToolkit\Operation\gd\CreateNew;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psr\Log\LoggerInterface;

/**
 * Tests deprecations of the image classes.
 */
#[Group('Image')]
#[RequiresPhpExtension('gd')]
#[IgnoreDeprecations]
class ImageDeprecationTest extends UnitTestCase {

  /**
   * Tests ImageToolkitOperationBase::__construct().
   */
  public function testToolkitArgumentInImageToolkitOperationConstructor(): void {
    $this->expectDeprecation('The $toolkit argument of Drupal\Core\ImageToolkit\ImageToolkitOperationBase::__construct is deprecated in drupal:11.4.0 and the argument is removed from drupal:13.0.0. Use ::setToolkit() instead. See https://www.drupal.org/node/3562304');
    new CreateNew([], '', [], $this->createStub(GDToolkit::class), $this->createStub(LoggerInterface::class));
  }

}
