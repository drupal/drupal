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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $block_manager = $this->createMock('Drupal\Core\Block\BlockManagerInterface');
    $block_manager->expects($this->any())
      ->method('getCategories')
      ->willReturn(['Comment', 'Node', 'None & Such', 'User']);

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
      return ['value' => $suggestion, 'label' => Html::escape($suggestion)];
    }, $suggestions);
    $result = $this->autocompleteController->autocomplete(new Request(['q' => $string]));
    $this->assertSame($suggestions, json_decode($result->getContent(), TRUE));
  }

  /**
   * Data provider for testAutocompleteSuggestions().
   *
   * @return array
   */
  public function providerTestAutocompleteSuggestions() {
    $test_parameters = [];
    $test_parameters[] = [
      'string' => 'Com',
      'suggestions' => [
        'Comment',
      ],
    ];
    $test_parameters[] = [
      'string' => 'No',
      'suggestions' => [
        'Node',
        'None & Such',
      ],
    ];
    $test_parameters[] = [
      'string' => 'us',
      'suggestions' => [
        'User',
      ],
    ];
    $test_parameters[] = [
      'string' => 'Banana',
      'suggestions' => [],
    ];
    return $test_parameters;
  }

}
