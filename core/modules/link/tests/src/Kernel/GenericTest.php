<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for link.
 */
#[Group('link')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
