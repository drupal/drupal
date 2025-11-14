<?php

declare(strict_types=1);

namespace Drupal\Tests\field_layout\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for field_layout.
 */
#[Group('field_layout')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
