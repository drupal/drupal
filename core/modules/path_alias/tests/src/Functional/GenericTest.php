<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for path_alias.
 */
#[Group('path_alias')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
