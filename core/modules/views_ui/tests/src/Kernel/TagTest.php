<?php

namespace Drupal\Tests\views_ui\Kernel;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views_ui\Controller\ViewsUIController;
use Drupal\Component\Utility\Html;
use Drupal\views\Entity\View;

/**
 * Tests the views ui tagging functionality.
 *
 * @group views_ui
 */
class TagTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'views_ui', 'user'];

  /**
   * Tests the ViewsUIController::autocompleteTag() function.
   */
  public function testViewsUiAutocompleteTag() {
    \Drupal::moduleHandler()->loadInclude('views_ui', 'inc', 'admin');

    // Save 15 views with a tag.
    $tags = [];
    for ($i = 0; $i < 16; $i++) {
      $suffix = $i % 2 ? 'odd' : 'even';
      $tag = 'autocomplete_tag_test_' . $suffix . $this->randomMachineName();
      $tags[] = $tag;
      View::create(['tag' => $tag, 'id' => $this->randomMachineName()])->save();
    }

    // Make sure just ten results are returned.
    $controller = ViewsUIController::create($this->container);
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $request->query->set('q', 'autocomplete_tag_test');
    $result = $controller->autocompleteTag($request);
    $matches = (array) json_decode($result->getContent(), TRUE);
    $this->assertCount(10, $matches, 'Make sure the maximum amount of tag results is 10.');

    // Make sure the returned array has the proper format.
    $suggestions = array_map(function ($tag) {
      return ['value' => $tag, 'label' => Html::escape($tag)];
    }, $tags);
    foreach ($matches as $match) {
      $this->assertContains($match, $suggestions, 'Make sure the returned array has the proper format.');
    }

    // Make sure that matching by a certain prefix works.
    $request->query->set('q', 'autocomplete_tag_test_even');
    $result = $controller->autocompleteTag($request);
    $matches = (array) json_decode($result->getContent(), TRUE);
    $this->assertCount(8, $matches, 'Make sure that only a subset is returned.');
    foreach ($matches as $tag) {
      $this->assertContains($tag['value'], $tags);
    }

    // Make sure an invalid result doesn't return anything.
    $request->query->set('q', $this->randomMachineName());
    $result = $controller->autocompleteTag($request);
    $matches = (array) json_decode($result->getContent());
    $this->assertCount(0, $matches, "Make sure an invalid tag doesn't return anything.");
  }

  /**
   * Tests that comma delimited tags are treated as individual tags.
   *
   * @dataProvider providerViewsUiAutocompleteIndividualTags
   */
  public function testViewsUiAutocompleteIndividualTags($expected_tag, $search_string) {
    $controller = ViewsUIController::create($this->container);
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $tag = 'comma, 你好, Foo bar';
    View::create(['tag' => $tag, 'id' => $this->randomMachineName()])->save();
    $request->query->set('q', $search_string);
    $result = $controller->autocompleteTag($request);
    $matches = (array) json_decode($result->getContent());
    $this->assertCount(1, $matches);
    $this->assertSame($expected_tag, $matches[0]->value);
  }

  /**
   * Data provider for testViewsUiAutocompleteIndividualTags().
   *
   * @return array[]
   *   The data set.
   */
  public function providerViewsUiAutocompleteIndividualTags() {
    return [
      'tag' => ['comma', 'comma'],
      'case insensitive tag' => ['comma', 'COMMA'],
      'Hello in Chinese (partial 1)' => ['你好', '你'],
      'Hello in Chinese (partial 2)' => ['你好', '好'],
      'Hello in Chinese' => ['你好', '你好'],
      'Starts with partial and case-sensitive' => ['Foo bar', 'Foo'],
      'Starts with partial and case-insensitive' => ['Foo bar', 'fOO'],
      'Ends with partial and case-sensitive' => ['Foo bar', 'bar'],
      'Ends with partial and case-insensitive' => ['Foo bar', 'BAR'],
    ];
  }

}
