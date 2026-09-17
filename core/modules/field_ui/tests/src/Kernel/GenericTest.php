<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for field_ui.
 */
#[Group('field_ui')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
