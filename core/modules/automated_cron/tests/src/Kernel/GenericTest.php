<?php

declare(strict_types=1);

namespace Drupal\Tests\automated_cron\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for automated_cron.
 */
#[Group('automated_cron')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
