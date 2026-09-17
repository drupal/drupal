<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for datetime.
 */
#[Group('datetime')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
