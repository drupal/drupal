<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for migrate_drupal_ui.
 */
#[Group('migrate_drupal_ui')]
#[ignoreDeprecations]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
