<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\MachineName;

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
  protected function setUp() {
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
   */
  public function testMachineNames() {

    // Tests the following transformations:
    // - non-alphanumeric character (including spaces) -> underscore,
    // - Uppercase -> lowercase,
    // - Multiple consecutive underscore -> single underscore.
    $human_name_ascii = 'foo2, the.bar;2*&the%baz!YEE____HaW ';
    $human_name = $human_name_ascii . 'áéő';
    $expected_result = 'foo2_the_bar_2_the_baz_yee_haw_aeo';
    // Test for calling transliterate on mock object.
    $this->transliteration
      ->expects($this->once())
      ->method('transliterate')
      ->with($human_name)
      ->will($this->returnValue($human_name_ascii . 'aeo'));

    $plugin = new MachineName([], 'machine_name', [], $this->transliteration);
    $value = $plugin->transform($human_name, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertEquals($expected_result, $value);
  }

}
