<?php

declare(strict_types=1);

namespace Drupal\Tests\page_cache\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for page_cache.
 */
#[Group('page_cache')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
