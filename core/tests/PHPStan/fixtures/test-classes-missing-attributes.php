<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\Tests\Core\Foo;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

abstract class AbstractTest extends BrowserTestBase {
}

class MissingAttributes extends WebDriverTestBase {
}

#[RunTestsInSeparateProcesses]
class MissingGroup extends BrowserTestBase {
}

#[Group('Test')]
class MissingRunTestsInSeparateProcesses extends KernelTestBase {
}

#[RunTestsInSeparateProcesses]
#[Group('Test')]
class Good extends BrowserTestBase {
}
