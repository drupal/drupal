<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for ckeditor5.
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
