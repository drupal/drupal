<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for image.
 */
#[Group('image')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
