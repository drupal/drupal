<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for dblog.
 */
#[Group('dblog')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
