<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for sqlite.
 */
#[Group('sqlite')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
