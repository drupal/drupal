<?php

declare(strict_types=1);

namespace Drupal\Tests\basic_auth\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for basic_auth.
 */
#[Group('basic_auth')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
