<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test\RunTests;

use Drupal\Core\Test\RunTests\TestFileParser;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Test\RunTests\TestFileParser
 * @group Test
 * @group RunTests
 */
class TestFileParserTest extends UnitTestCase {

  public function provideTestFileContents() {
    return [
      'empty' => [[], ''],
      'no-namespace' => [['ConcreteClass'],
       <<< 'NO_NAMESPACE'
<?php

class ConcreteClass {}
NO_NAMESPACE
      ],
      'concrete' => [['Namespace\Is\Complex\ConcreteClass'],
       <<< 'CONCRETE_CLASS'
<?php

namespace Namespace\Is\Complex;

class ConcreteClass {}
CONCRETE_CLASS
      ],
      'abstract' => [[],
       <<< 'ABSTRACT_CLASS'
<?php
namespace Namespace\Is\Complex;

abstract class AbstractClass {}
ABSTRACT_CLASS
      ],
      'final' => [['Namespace\Is\Complex\FinalClass'],
       <<< 'FINAL_CLASS'
<?php
namespace Namespace\Is\Complex;

final class FinalClass {}
FINAL_CLASS
      ],
      'compound_declarations' => [[
        'Namespace\Is\Complex\FinalClass',
        'Namespace\Is\Complex\AnotherClass',
      ],
       <<< 'COMPOUND'
<?php
namespace Namespace\Is\Complex;

final class FinalClass {}

class AnotherClass {}
COMPOUND
      ],
    ];
  }

  /**
   * @covers ::parseContents
   * @dataProvider provideTestFileContents
   */
  public function testParseContents($expected, $contents) {
    $parser = new TestFileParser();

    $ref_parse = new \ReflectionMethod($parser, 'parseContents');

    $this->assertSame($expected, $ref_parse->invoke($parser, $contents));
  }

  /**
   * @covers ::getTestListFromFile
   */
  public function testGetTestListFromFile() {
    $parser = new TestFileParser();
    $this->assertEquals(
      ['Drupal\Tests\Core\Test\RunTests\TestFileParserTest'],
      $parser->getTestListFromFile(__FILE__)
    );
    $this->assertEquals(
      ['Drupal\KernelTests\Core\Datetime\Element\TimezoneTest'],
      $parser->getTestListFromFile(__DIR__ . '/../../../../KernelTests/Core/Datetime/Element/TimezoneTest.php')
    );
  }

}
