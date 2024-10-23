<?php

declare(strict_types=1);

namespace Drupal\Tests\PhpCs;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Tests that phpcs.xml.dist is properly sorted.
 *
 * @group phpcs
 */
class SortTest extends TestCase {

  /**
   * The path of phpcs.xml.dist file.
   *
   * @var string
   */
  private $filePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->filePath = __DIR__ . '/../../../../../core/phpcs.xml.dist';
  }

  /**
   * Tests that the phpcs.xml.dist file exists.
   */
  public function testFileExists(): void {
    $this->assertFileExists($this->filePath);
  }

  /**
   * Tests that the phpcs.xml.dist file is properly sorted.
   */
  public function testSorted(): void {
    $content = file_get_contents($this->filePath);
    $xml_encoder = new XmlEncoder();
    $xml_encoded = $xml_encoder->decode($content, 'xml');
    $this->assertIsArray($xml_encoded);

    $top_level_keys = array_keys($xml_encoded);
    $this->assertSorted($top_level_keys);

    $this->assertArrayHasKey('file', $xml_encoded);
    $files = $xml_encoded['file'];
    $this->assertSorted($files);

    $this->assertArrayHasKey('exclude-pattern', $xml_encoded);
    $excluded_patterns = $xml_encoded['exclude-pattern'];
    $this->assertSorted($excluded_patterns);

    $this->assertArrayHasKey('rule', $xml_encoded);
    $rules = $xml_encoded['rule'];
    $this->assertSorted($rules, '@ref');

    foreach ($rules as $item) {
      if (array_key_exists('exclude', $item)) {
        $excluded = $item['exclude'];
        $excluded = array_filter($excluded, static function ($item) {
          return is_array($item) && array_key_exists('@name', $item);
        });
        $this->assertSorted($excluded, '@name');
      }
    }
  }

  /**
   * A helper method to assert that an input array is sorted.
   *
   * Compared by values, if the $column is not null, the column of the value is
   * used for comparing.
   *
   * @param array $input
   *   The input array.
   * @param null|string $column
   *   The column of the value or NULL.
   */
  private function assertSorted(array $input, ?string $column = NULL): void {
    $input_sorted = $input;

    if ($column === NULL) {
      usort($input_sorted, static function ($a, $b) {
        return strcmp($a, $b);
      });
    }
    else {
      usort($input_sorted, static function ($a, $b) use ($column) {
        return strcmp($a[$column], $b[$column]);
      });
    }

    $this->assertEquals($input, $input_sorted);
  }

}
