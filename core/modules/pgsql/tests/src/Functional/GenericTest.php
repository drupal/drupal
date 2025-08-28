<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Generic module test for pgsql.
 */
#[Group('pgsql')]
class GenericTest extends GenericModuleTestBase {}
