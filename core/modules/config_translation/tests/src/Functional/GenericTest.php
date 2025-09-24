<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for config_translation.
 */
#[Group('config_translation')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
