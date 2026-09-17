<?php

declare(strict_types=1);

namespace Drupal\Tests\inline_form_errors\Kernel;

use Drupal\Tests\system\Kernel\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for inline_form_errors.
 */
#[Group('inline_form_errors')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
