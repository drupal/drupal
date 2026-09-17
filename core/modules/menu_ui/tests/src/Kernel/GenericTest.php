<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for menu_ui.
 */
#[Group('menu_ui')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
