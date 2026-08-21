<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for shortcut.
 */
#[Group('shortcut')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
