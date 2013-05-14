<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Transliteration\MachineNameControllerTest.
 */

namespace Drupal\system\Tests\Transliteration;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Component\Transliteration\PHPTransliteration;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\system\MachineNameController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests machine name controller's transliteration functionality.
 */
class MachineNameControllerTest extends DrupalUnitTestBase {

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
   * Tests machine name controller's transliteration functionality.
   *
   * @see \Drupal\system\MachineNameController::transliterate()
   */
  public function testMachineNameController() {
    $request = Request::create('', 'GET', array('text' => 'Bob', 'langcode' => 'en'));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"Bob"', $json->getContent());

    $request = Request::create('', 'GET', array('text' => 'Bob', 'langcode' => 'en', 'lowercase' => TRUE));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"bob"', $json->getContent());

    $request = Request::create('', 'GET', array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Bob'));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"Alice"', $json->getContent());

    $request = Request::create('', 'GET', array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Tom'));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"Bob"', $json->getContent());

    $request = Request::create('', 'GET', array('text' => 'Äwesome', 'langcode' => 'en', 'lowercase' => TRUE));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"awesome"', $json->getContent());

    $request = Request::create('', 'GET', array('text' => 'Äwesome', 'langcode' => 'de', 'lowercase' => TRUE));
    $json = $this->machineNameController->transliterate($request);
    $this->assertEqual('"aewesome"', $json->getContent());

  }

}
