<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_discovery\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for layout_discovery.
 */
#[Group('layout_discovery')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
