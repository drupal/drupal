<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\Tests\Core\Foo;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * @group metadata
 * @coversNothing
 */
#[RunTestsInSeparateProcesses]
abstract class BarTest extends TestCase {
}

abstract class QuxTest extends TestCase {
}

/**
 * With some docs.
 *
 * @internal
 */
abstract class SeeTest extends TestCase {
}

/**
 * @group metadata
 */
class ConcreteWithAnnotationTest extends TestCase {
}

#[Group('Test')]
class ConcreteWithAttributeTest extends TestCase {
}

class NotATestClass {
}
