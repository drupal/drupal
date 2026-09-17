<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for package_manager.
 */
#[Group('package_manager')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
