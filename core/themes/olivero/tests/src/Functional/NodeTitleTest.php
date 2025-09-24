<?php

declare(strict_types=1);

namespace Drupal\Tests\olivero\Functional;

use Drupal\Tests\system\Functional\Theme\NodeTitleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node title for olivero.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeTitleTest extends NodeTitleTestBase {

}
