<?php

declare(strict_types=1);

namespace Drupal\Tests\dynamic_page_cache\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for dynamic_page_cache.
 */
#[Group('dynamic_page_cache')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
