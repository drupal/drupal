<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for update.
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
