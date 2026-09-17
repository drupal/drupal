<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for announcements_feed.
 */
#[Group('announcements_feed')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
