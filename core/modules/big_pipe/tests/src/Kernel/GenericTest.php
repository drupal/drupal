<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for big_pipe.
 */
#[Group('big_pipe')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
