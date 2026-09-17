<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for block_content.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
