<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for navigation.
 */
#[Group('navigation')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
