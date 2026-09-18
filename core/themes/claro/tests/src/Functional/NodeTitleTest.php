<?php

declare(strict_types=1);

namespace Drupal\Tests\claro\Functional;

use Drupal\Tests\system\Functional\Theme\NodeTitleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node title for claro.
 */
#[Group('node')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class NodeTitleTest extends NodeTitleTestBase {

}
