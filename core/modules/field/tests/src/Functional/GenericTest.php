<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for field.
 */
#[Group('field')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
