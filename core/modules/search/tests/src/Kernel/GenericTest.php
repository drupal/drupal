<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for search.
 */
#[Group('search')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
