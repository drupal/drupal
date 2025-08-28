<?php

declare(strict_types=1);

namespace Drupal\Tests\ban\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Generic module test for ban.
 */
#[Group('ban')]
#[IgnoreDeprecations]
class GenericTest extends GenericModuleTestBase {}
