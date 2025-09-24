<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for content_translation.
 */
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
