<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Transliteration\MachineNameControllerTest.
 */

namespace Drupal\system\Tests\Transliteration;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Transliteration\PHPTransliteration;
use Drupal\system\MachineNameController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests machine name controller's transliteration functionality.
 *
 * @group System
 */
class MachineNameControllerTest extends UnitTestCase {

  /**
   * The machine name controller.
   *
   * @var \Drupal\system\MachineNameController
   */
  protected $machineNameController;

  public static function getInfo() {
    return array(
      'name' => 'Machine name controller tests',
      'description' => 'Tests that the machine name controller can transliterate strings as expected.',
      'group' => 'Transliteration',
    );
  }

  public function setUp() {
    parent::setUp();
    // Create the machine name controller.
    $this->machineNameController = new MachineNameController(new PHPTransliteration());
  }

  /**
   * Data provider for testMachineNameController().
   *
   * @see testMachineNameController()
   *
   * @return array
   *   An array containing:
   *     - An array of request parameters.
   *     - The expected content of the JSONresponse.
   */
  public function providerTestMachineNameController() {
    return array(
      array(array('text' => 'Bob', 'langcode' => 'en'), '"Bob"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'lowercase' => TRUE), '"bob"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Bob'), '"Alice"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Tom'), '"Bob"'),
      array(array('text' => 'Äwesome', 'langcode' => 'en', 'lowercase' => TRUE), '"awesome"'),
      array(array('text' => 'Äwesome', 'langcode' => 'de', 'lowercase' => TRUE), '"aewesome"'),
    );
  }

  /**
   * Tests machine name controller's transliteration functionality.
   *
   * @param array $request_params
   *   An array of request parameters.
   * @param $expected_content
   *   The expected content of the JSONresponse.
   *
   * @see \Drupal\system\MachineNameController::transliterate()
   *
   * @dataProvider providerTestMachineNameController
   */
  public function testMachineNameController(array $request_params, $expected_content) {
    $request = Request::create('', 'GET', $request_params);
    $json = $this->machineNameController->transliterate($request);
    $this->assertEquals($expected_content, $json->getContent());
  }

}
