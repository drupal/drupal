<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for system.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
