<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Recipe\Core\article_comment;

use Drupal\Tests\system\Functional\Recipe\GenericRecipeTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Generic.
 */
#[Group('core_article_comment_recipe')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericRecipeTestBase {}
