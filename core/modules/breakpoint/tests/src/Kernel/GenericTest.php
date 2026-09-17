<?php

declare(strict_types=1);

namespace Drupal\Tests\breakpoint\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for breakpoint.
 */
#[Group('breakpoint')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
