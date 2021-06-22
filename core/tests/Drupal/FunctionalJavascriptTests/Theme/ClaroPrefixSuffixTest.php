<?php

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests prefix and suffix behavior in Claro.
 *
 * @group claro
 */
class ClaroPrefixSuffixTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'prefix_suffix_test',
  ];

  /**
   * Hellpo.
   *
   * @param int $width
   *   The viewport width to run the tests at.
   * @param array[] $fields
   *   An array that lists each field being tested with the following keys:
   *   - selector_string: A substring shared by various elements in the input
   *     being tested.
   *   - prefix: FALSE if one is not present, "stacked" if expected to appear
   *     above the input, "unstacked" if expected to appear next to the input.
   *   - suffix: FALSE if one is not present, "stacked" if expected to appear
   *     below the input, "unstacked" if expected to appear next to the input.
   *
   * @dataProvider providerTestPrefixSuffix
   */
  public function testPrefixSuffix($width, array $fields) {
    $this->drupalGet('prefix-suffix-test/form');
    $page = $this->getSession()->getPage();
    $this->getSession()->resizeWindow($width, 1800);

    // If the width is narrow enough to result in stacking, wait for the stacked
    // classes to appear before continuing.
    if ($width < 1600) {
      $this->assertSession()->waitForElementVisible('css', '.form-item__affix--stacked');
    }
    foreach ($fields as $field) {
      $wrapper_classes = [
        'form-item__wrapper',
        'form-item__wrapper--with-affix',
      ];
      $affix_classes = [
        'prefix' => [
          'form-item__affix',
          'form-item__prefix',
        ],
        'suffix' => [
          'form-item__affix',
          'form-item__suffix',
        ],
      ];

      $selector_string = $field['selector_string'];
      $input = $page->findById("edit-$selector_string");
      $this->assertNotNull($input);
      $input_y_position = $this->getElementVerticalPosition("#edit-$selector_string");
      $wrapper = $input->getParent();
      $prefix = $wrapper->find('css', '[data-drupal-form-item-prefix]');
      $suffix = $wrapper->find('css', '[data-drupal-form-item-suffix]');

      foreach (['prefix', 'suffix'] as $affix) {
        if (!$field[$affix]) {
          $this->assertNull(${$affix});
        }
        else {
          $affix_y_position = $this->getElementVerticalPosition(".form-item--$selector_string [data-drupal-form-item-$affix]");
          $wrapper_classes[] = "form-item__wrapper--with-$affix";
          if ($field[$affix] === 'stacked') {
            // If the affix is expected to be stacked, confirm it actually has
            // a different Y position.
            $this->assertNotEquals($input_y_position, $affix_y_position, $selector_string);

            // Include the "stacked" classes specfic to this affix type in the
            // classes expected to be present.
            $wrapper_classes[] = "form-item__wrapper--stacked-$affix";
            $affix_classes[$affix][] = 'form-item__affix--stacked';
            $affix_classes[$affix][] = "form-item__$affix--stacked";
          }
          else {
            $this->assertEquals($input_y_position, $affix_y_position, $selector_string);
          }
          foreach ($affix_classes[$affix] as $class) {
            $this->assertTrue(${$affix}->hasClass($class));
          }
        }
      }
      foreach ($wrapper_classes as $class) {
        $this->assertTrue($wrapper->hasClass($class));
      }
      $wrapper_classes_that_should_not_be_there = array_diff([
        'form-item__wrapper--stacked-prefix',
        'form-item__wrapper--stacked-suffix',
      ], $wrapper_classes);
      foreach ($wrapper_classes_that_should_not_be_there as $class) {
        $this->assertFalse($wrapper->hasClass($class));
      }
    }
  }

  /**
   * Gets the element Y position.
   *
   * @param string $css_selector
   *   The CSS selector of the element.
   *
   * @return int
   *   The element Y position.
   */
  protected function getElementVerticalPosition($css_selector) {
    return (int) $this->getSession()->evaluateScript("document.querySelector('$css_selector').getBoundingClientRect().top");
  }

  /**
   * Data provider for testing affixes at different screen widths.
   *
   * @return array[]
   *   The viewport width to test, and expected results for each input.
   *   Each item in 'inputs' has the following keys:
   *   - selector_string: A substring shared by various elements in the input
   *     being tested.
   *   - prefix: FALSE if one is not present, "stacked" if expected to appear
   *     above the input, "unstacked" if expected to appear next to the input.
   *   - suffix: FALSE if one is not present, "stacked" if expected to appear
   *     below the input, "unstacked" if expected to appear next to the input.
   */
  public function providerTestPrefixSuffix() {
    return [
      'width1600' => [
        'width' => 1600,
        'inputs' => [
          [
            'selector_string' => 'standard-prefix',
            'prefix' => 'unstacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'standard-suffix',
            'prefix' => FALSE,
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'standard-prefix-standard-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'long-prefix',
            'prefix' => 'unstacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'long-suffix',
            'prefix' => FALSE,
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'long-prefix-standard-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'long-prefix-standard-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'standard-prefix-long-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'unstacked',
          ],
        ],
      ],
      'width1000' => [
        'width' => 1000,
        'inputs' => [
          [
            'selector_string' => 'standard-prefix',
            'prefix' => 'unstacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'standard-suffix',
            'prefix' => FALSE,
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'standard-prefix-standard-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'long-prefix',
            'prefix' => 'stacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'long-suffix',
            'prefix' => FALSE,
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'long-prefix-long-suffix',
            'prefix' => 'stacked',
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'long-prefix-standard-suffix',
            'prefix' => 'stacked',
            'suffix' => 'unstacked',
          ],
          [
            'selector_string' => 'standard-prefix-long-suffix',
            'prefix' => 'unstacked',
            'suffix' => 'stacked',
          ],
        ],
      ],
      'width600' => [
        'width' => 600,
        'inputs' => [
          [
            'selector_string' => 'standard-prefix',
            'prefix' => 'stacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'standard-suffix',
            'prefix' => FALSE,
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'standard-prefix-standard-suffix',
            'prefix' => 'stacked',
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'long-prefix',
            'prefix' => 'stacked',
            'suffix' => FALSE,
          ],
          [
            'selector_string' => 'long-suffix',
            'prefix' => FALSE,
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'long-prefix-long-suffix',
            'prefix' => 'stacked',
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'long-prefix-standard-suffix',
            'prefix' => 'stacked',
            'suffix' => 'stacked',
          ],
          [
            'selector_string' => 'standard-prefix-long-suffix',
            'prefix' => 'stacked',
            'suffix' => 'stacked',
          ],
        ],
      ],
    ];
  }

}
