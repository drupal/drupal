<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\FrontMatter;

use Drupal\Component\FrontMatter\Exception\FrontMatterParseException;
use Drupal\Component\FrontMatter\FrontMatter;
use Drupal\Component\Serialization\Yaml;
use PHPUnit\Framework\TestCase;

/**
 * Tests front matter parsing helper methods.
 *
 * @group FrontMatter
 *
 * @coversDefaultClass \Drupal\Component\FrontMatter\FrontMatter
 */
class FrontMatterTest extends TestCase {

  /**
   * A basic source string.
   */
  const SOURCE = '<div>Hello world</div>';

  /**
   * Creates a front matter source string.
   *
   * @param array|null $yaml
   *   The YAML array to prepend as a front matter block.
   * @param string $content
   *   The source contents.
   *
   * @return string
   *   The new source.
   */
  public static function createFrontMatterSource(?array $yaml, string $content = self::SOURCE): string {
    // Encode YAML and wrap in a front matter block.
    $frontMatter = '';
    if (is_array($yaml)) {
      $yaml = $yaml ? trim(Yaml::encode($yaml)) . "\n" : '';
      $frontMatter = FrontMatter::SEPARATOR . "\n$yaml" . FrontMatter::SEPARATOR . "\n";
    }
    return $frontMatter . $content;
  }

  /**
   * Tests when a passed serializer doesn't implement the proper interface.
   *
   * @covers ::__construct
   * @covers ::create
   */
  public function testFrontMatterSerializerException() {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('The $serializer parameter must reference a class that implements Drupal\Component\Serialization\SerializationInterface.');
    FrontMatter::create('', '');
  }

  /**
   * Tests broken front matter.
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::parse
   * @covers \Drupal\Component\FrontMatter\Exception\FrontMatterParseException
   */
  public function testFrontMatterBroken() {
    $this->expectException(FrontMatterParseException::class);
    $this->expectExceptionMessage('An error occurred when attempting to parse front matter data on line 4');
    $source = "---\ncollection:\n-  key: foo\n  foo: bar\n---\n";
    FrontMatter::create($source)->getData();
  }

  /**
   * Tests the parsed data from front matter.
   *
   * @param array|null $yaml
   *   The YAML used as front matter data to prepend the source.
   * @param int $line
   *   The expected line number where the source code starts.
   * @param string $content
   *   The content to use for testing purposes.
   *
   * @covers ::__construct
   * @covers ::getContent
   * @covers ::getData
   * @covers ::getLine
   * @covers ::create
   * @covers ::parse
   *
   * @dataProvider providerFrontMatterData
   */
  public function testFrontMatterData($yaml, $line, $content = self::SOURCE) {
    $source = static::createFrontMatterSource($yaml, $content);
    $frontMatter = FrontMatter::create($source);
    $this->assertEquals($content, $frontMatter->getContent());
    $this->assertEquals($yaml === NULL ? [] : $yaml, $frontMatter->getData());
    $this->assertEquals($line, $frontMatter->getLine());
  }

  /**
   * Provides the front matter data to test.
   *
   * @return array
   *   Array of front matter data.
   */
  public static function providerFrontMatterData() {
    $data['none'] = [
      'yaml' => NULL,
      'line' => 1,
    ];
    $data['scalar'] = [
      'yaml' => [
        'string' => 'value',
        'number' => 42,
        'bool' => TRUE,
        'null' => NULL,
      ],
      'line' => 7,
    ];
    $data['indexed_arrays'] = [
      'yaml' => [
        'brackets' => [1, 2, 3],
        'items' => [
          'item1',
          'item2',
          'item3',
        ],
      ],
      'line' => 11,
    ];
    $data['associative_arrays'] = [
      'yaml' => [
        'brackets' => [
          'a' => 1,
          'b' => 2,
          'c' => 3,
        ],
        'items' => [
          'a' => 'item1',
          'b' => 'item2',
          'c' => 'item3',
        ],
      ],
      'line' => 11,
    ];
    $data['empty_data'] = [
      'yaml' => [],
      'line' => 3,
    ];
    $data['empty_content'] = [
      'yaml' => ['key' => 'value'],
      'line' => 4,
      'content' => '',
    ];
    $data['empty_data_and_content'] = [
      'yaml' => [],
      'line' => 3,
      'content' => '',
    ];
    $data['empty_string'] = [
      'yaml' => NULL,
      'line' => 1,
      'content' => '',
    ];
    $data['multiple_separators'] = [
      'yaml' => ['key' => '---'],
      'line' => 4,
      'content' => "Something\n---\nSomething more",
    ];
    return $data;
  }

}
