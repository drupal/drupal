<?php

declare(strict_types=1);

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for responsive_image.
 */
#[Group('responsive_image')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
