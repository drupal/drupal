<?php

namespace Drupal\Tests\CSpell;

use PHPUnit\Framework\TestCase;

/**
 * Tests that the dictionary.txt file is properly sorted.
 *
 * @group cspell
 */
class SortTest extends TestCase {

  /**
   * The path to the dictionary file.
   */
  private string $filePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->filePath = dirname(__DIR__, 5) . '/core/misc/cspell/dictionary.txt';
  }

  /**
   * Tests that the file exists.
   */
  public function testFileExists() {
    $this->assertFileExists($this->filePath);
  }

  /**
   * Tests that the file is properly sorted.
   */
  public function testSorted() {
    $content = file_get_contents($this->filePath);
    $this->assertIsString($content);
    $current_dictionary = explode("\n", rtrim($content));
    $this->assertIsArray($current_dictionary);
    $sorted_dictionary = $current_dictionary;
    sort($current_dictionary);
    $this->assertSame($current_dictionary, $sorted_dictionary);
  }

}
