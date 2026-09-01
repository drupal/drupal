<?php

declare(strict_types=1);

namespace Drupal\Tests\text_with_summary\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for text_with_summary.
 */
#[Group('text_with_summary')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
