<?php

namespace Drupal\Tests\Core\Test;

use Drupal\KernelTests\AssertContentTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\KernelTests\AssertContentTrait
 * @group Test
 */
class AssertContentTraitTest extends UnitTestCase {

  /**
   * @covers ::getTextContent
   */
  public function testGetTextContent() {
    $test = new TestClass();
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
    $test->_setRawContent($raw_content);
    $this->assertStringNotContainsString('foo', $test->_getTextContent());
    $this->assertStringNotContainsString('<body>', $test->_getTextContent());
    $this->assertStringContainsString('bar', $test->_getTextContent());
  }

}

class TestClass {
  use AssertContentTrait;

  public function _setRawContent($content) {
    $this->setRawContent($content);
  }

  public function _getTextContent() {
    return $this->getTextContent();
  }

}
