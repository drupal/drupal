<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for contact.
 */
#[Group('contact')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {}
