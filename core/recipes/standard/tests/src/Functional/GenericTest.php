<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Recipe\Core\standard;

use Drupal\Tests\system\Functional\Recipe\GenericRecipeTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Generic.
 */
#[Group('core_standard_recipe')]
#[Group('#slow')]
class GenericTest extends GenericRecipeTestBase {}
