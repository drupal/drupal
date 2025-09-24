<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for block_content.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
