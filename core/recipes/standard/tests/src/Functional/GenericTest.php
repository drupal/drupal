<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Recipe\Core\standard;

use Drupal\Tests\system\Functional\Recipe\GenericRecipeTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Generic.
 */
#[Group('core_standard_recipe')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericRecipeTestBase {}
