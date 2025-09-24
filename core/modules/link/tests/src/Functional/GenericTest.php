<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for link.
 */
#[Group('link')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
