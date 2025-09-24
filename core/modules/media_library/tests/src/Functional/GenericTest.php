<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for media_library.
 */
#[Group('media_library')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
