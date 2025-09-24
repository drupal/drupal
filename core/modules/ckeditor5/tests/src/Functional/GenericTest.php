<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for ckeditor5.
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
