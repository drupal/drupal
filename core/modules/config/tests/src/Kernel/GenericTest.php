<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for config.
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
