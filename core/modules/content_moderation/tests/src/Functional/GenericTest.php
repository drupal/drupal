<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for content_moderation.
 */
#[Group('content_moderation')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
