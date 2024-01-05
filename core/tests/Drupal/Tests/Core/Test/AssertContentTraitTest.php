<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\KernelTests\AssertContentTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\KernelTests\AssertContentTrait
 * @group Test
 */
class AssertContentTraitTest extends UnitTestCase {

  use AssertContentTrait;

  /**
   * @covers ::getTextContent
   */
  public function testGetTextContent() {

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
