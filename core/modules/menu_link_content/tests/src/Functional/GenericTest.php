<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for menu_link_content.
 */
#[Group('menu_link_content')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
