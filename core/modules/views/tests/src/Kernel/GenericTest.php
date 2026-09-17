<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for views.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
