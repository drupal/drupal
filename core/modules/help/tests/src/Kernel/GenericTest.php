<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for help.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
