<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for help.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
