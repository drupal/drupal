<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\MachineName;
use Drupal\migrate\MigrateException;

/**
 * Tests the machine name process plugin.
 *
 * @group migrate
 */
class MachineNameTest extends MigrateProcessTestCase {

  /**
   * The mock transliteration.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->transliteration = $this->getMockBuilder('Drupal\Component\Transliteration\TransliterationInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $this->migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    parent::setUp();
  }

  /**
   * Tests machine name transformation of non-alphanumeric characters.
   *
   * @param string $human_name
   *   The human-readable name that will be converted in the test.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $expected_result
   *   The expected result of the transformation.
   *
   * @dataProvider providerTestMachineNames
   */
  public function testMachineNames(string $human_name, array $configuration, string $expected_result): void {
    // Test for calling transliterate on mock object.
    $this->transliteration
      ->expects($this->once())
      ->method('transliterate')
      ->with($human_name)
      ->will($this->returnCallback(function (string $string): string {
        return str_replace(['á', 'é', 'ő'], ['a', 'e', 'o'], $string);
      }));

    $plugin = new MachineName($configuration, 'machine_name', [], $this->transliteration);
    $value = $plugin->transform($human_name, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertEquals($expected_result, $value);
  }

  /**
   * Provides test cases for MachineNameTest::testMachineNames().
   *
   * @return array
   *   An array of test cases.
   */
  public function providerTestMachineNames(): array {
    return [
      // Tests the following transformations:
      // - non-alphanumeric character (including spaces) -> underscore,
      // - Uppercase -> lowercase,
      // - Multiple consecutive underscore -> single underscore.
      'default' => [
        'human_name' => 'foo2, the.bar;2*&the%baz!YEE____HaW áéő',
        'configuration' => [],
        'expected_result' => 'foo2_the_bar_2_the_baz_yee_haw_aeo',
      ],
      // Tests with a different pattern that allows periods.
      'period_allowed' => [
        'human_name' => '2*&the%baz!YEE____HaW áéő.jpg',
        'configuration' => [
          'replace_pattern' => '/[^a-z0-9_.]+/',
        ],
        'expected_result' => '2_the_baz_yee_haw_aeo.jpg',
      ],
    ];
  }

  /**
   * Tests that the replacement regular expression is a string.
   */
  public function testInvalidConfiguration(): void {
    $configuration['replace_pattern'] = 1;
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The replace pattern should be a string');
    new MachineName($configuration, 'machine_name', [], $this->transliteration);
  }

}
