<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for views_ui.
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
