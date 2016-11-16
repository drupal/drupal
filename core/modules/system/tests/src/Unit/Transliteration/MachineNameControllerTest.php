<?php

namespace Drupal\Tests\system\Unit\Transliteration;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\system\MachineNameController;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that the machine name controller can transliterate strings as expected.
 *
 * @group system
 */
class MachineNameControllerTest extends UnitTestCase {

  /**
   * The machine name controller.
   *
   * @var \Drupal\system\MachineNameController
   */
  protected $machineNameController;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  protected function setUp() {
    parent::setUp();
    // Create the machine name controller.
    $this->tokenGenerator = $this->prophesize(CsrfTokenGenerator::class);
    $this->tokenGenerator->validate(Argument::cetera())->will(function ($args) {
      return $args[0] === 'token-' . $args[1];
    });

    $this->machineNameController = new MachineNameController(new PhpTransliteration(), $this->tokenGenerator->reveal());
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
    $valid_data = array(
      array(array('text' => 'Bob', 'langcode' => 'en'), '"Bob"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'lowercase' => TRUE), '"bob"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Bob'), '"Alice"'),
      array(array('text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Tom'), '"Bob"'),
      array(array('text' => 'Äwesome', 'langcode' => 'en', 'lowercase' => TRUE), '"awesome"'),
      array(array('text' => 'Äwesome', 'langcode' => 'de', 'lowercase' => TRUE), '"aewesome"'),
      // Tests special characters replacement in the input text.
      array(array('text' => 'B?!"@\/-ob@e', 'langcode' => 'en', 'lowercase' => TRUE, 'replace' => '_', 'replace_pattern' => '[^a-z0-9_.]+'), '"b_ob_e"'),
      // Tests @ character in the replace_pattern regex.
      array(array('text' => 'Bob@e\0', 'langcode' => 'en', 'lowercase' => TRUE, 'replace' => '_', 'replace_pattern' => '[^a-z0-9_.@]+'), '"bob@e_0"'),
      // Tests null byte in the replace_pattern regex.
      array(array('text' => 'Bob', 'langcode' => 'en', 'lowercase' => TRUE, 'replace' => 'fail()', 'replace_pattern' => ".*@e\0"), '"bob"'),
      array(array('text' => 'Bob@e', 'langcode' => 'en', 'lowercase' => TRUE, 'replace' => 'fail()', 'replace_pattern' => ".*@e\0"), '"fail()"'),
    );

    $valid_data = array_map(function ($data) {
      if (isset($data[0]['replace_pattern'])) {
        $data[0]['replace_token'] = 'token-' . $data[0]['replace_pattern'];
      }
      return $data;
    }, $valid_data);

    return $valid_data;
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

  /**
   * Tests the pattern validation.
   */
  public function testMachineNameControllerWithInvalidReplacePattern() {
    $request = Request::create('', 'GET', ['text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Bob', 'replace_token' => 'invalid']);

    $this->setExpectedException(AccessDeniedException::class, "Invalid 'replace_token' query parameter.");
    $this->machineNameController->transliterate($request);
  }

  /**
   * Tests the pattern validation with a missing token.
   */
  public function testMachineNameControllerWithMissingToken() {
    $request = Request::create('', 'GET', ['text' => 'Bob', 'langcode' => 'en', 'replace' => 'Alice', 'replace_pattern' => 'Bob']);

    $this->setExpectedException(AccessDeniedException::class, "Missing 'replace_token' query parameter.");
    $this->machineNameController->transliterate($request);
  }

}
