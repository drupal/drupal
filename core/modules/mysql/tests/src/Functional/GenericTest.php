<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for mysql.
 */
#[Group('mysql')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
