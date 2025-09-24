<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Recipe\Core\image_media_type;

use Drupal\Tests\system\Functional\Recipe\GenericRecipeTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Generic.
 */
#[Group('core_image_media_type_recipe')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericRecipeTestBase {}
