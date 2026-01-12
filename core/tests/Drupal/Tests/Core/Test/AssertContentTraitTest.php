<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\KernelTests\AssertContentTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\KernelTests\AssertContentTrait.
 */
#[CoversClass(AssertContentTrait::class)]
#[Group('Test')]
class AssertContentTraitTest extends UnitTestCase {

  use AssertContentTrait;

  /**
   * Tests get text content.
   */
  public function testGetTextContent(): void {

    $raw_content = <<<EOT

<Head>
<style>
@import url("foo.css");
</style>
</head>
<body>
bar
</body>
EOT;
    $this->setRawContent($raw_content);
    $this->assertStringNotContainsString('foo', $this->getTextContent());
    $this->assertStringNotContainsString('<body>', $this->getTextContent());
    $this->assertStringContainsString('bar', $this->getTextContent());
  }

}
