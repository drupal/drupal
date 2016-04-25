<?php

namespace Drupal\Tests\block\Unit;

use Drupal\Component\Utility\Html;
use Drupal\block\Controller\CategoryAutocompleteController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\block\Controller\CategoryAutocompleteController
 * @group block
 */
class CategoryAutocompleteTest extends UnitTestCase {

  /**
   * The autocomplete controller.
   *
   * @var \Drupal\block\Controller\CategoryAutocompleteController
   */
  protected $autocompleteController;

  protected function setUp() {
    $block_manager = $this->getMock('Drupal\Core\Block\BlockManagerInterface');
    $block_manager->expects($this->any())
      ->method('getCategories')
      ->will($this->returnValue(array('Comment', 'Node', 'None & Such', 'User')));

    $this->autocompleteController = new CategoryAutocompleteController($block_manager);
  }

  /**
   * Tests the autocomplete method.
   *
   * @param string $string
   *   The string entered into the autocomplete.
   * @param array $suggestions
   *   The array of expected suggestions.
   *
   * @see \Drupal\block\Controller\CategoryAutocompleteController::autocomplete()
   *
   * @dataProvider providerTestAutocompleteSuggestions
   */
  public function testAutocompleteSuggestions($string, $suggestions) {
    $suggestions = array_map(function ($suggestion) {
      return array('value' => $suggestion, 'label' => Html::escape($suggestion));
    }, $suggestions);
    $result = $this->autocompleteController->autocomplete(new Request(array('q' => $string)));
    $this->assertSame($suggestions, json_decode($result->getContent(), TRUE));
  }

  /**
   * Data provider for testAutocompleteSuggestions().
   *
   * @return array
   */
  public function providerTestAutocompleteSuggestions() {
    $test_parameters = array();
    $test_parameters[] = array(
      'string' => 'Com',
      'suggestions' => array(
        'Comment',
      ),
    );
    $test_parameters[] = array(
      'string' => 'No',
      'suggestions' => array(
        'Node',
        'None & Such',
      ),
    );
    $test_parameters[] = array(
      'string' => 'us',
      'suggestions' => array(
        'User',
      ),
    );
    $test_parameters[] = array(
      'string' => 'Banana',
      'suggestions' => array(),
    );
    return $test_parameters;
  }

}
