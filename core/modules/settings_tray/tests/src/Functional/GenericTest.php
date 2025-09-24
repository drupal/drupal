<?php

declare(strict_types=1);

namespace Drupal\Tests\settings_tray\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for settings_tray.
 */
#[Group('settings_tray')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
