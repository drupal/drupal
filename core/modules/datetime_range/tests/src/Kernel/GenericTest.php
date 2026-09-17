<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for datetime_range.
 */
#[Group('datetime_range')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
