<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for options.
 */
#[Group('options')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
