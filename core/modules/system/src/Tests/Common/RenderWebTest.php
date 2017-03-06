<?php

namespace Drupal\system\Tests\Common;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Performs integration tests on drupal_render().
 *
 * @group Common
 */
class RenderWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['common_test'];

  /**
   * Asserts the cache context for the wrapper format is always present.
   */
  public function testWrapperFormatCacheContext() {
    $this->drupalGet('common-test/type-link-active-class');
    $this->assertIdentical(0, strpos($this->getRawContent(), "<!DOCTYPE html>\n<html"));
    $this->assertIdentical('text/html; charset=UTF-8', $this->drupalGetHeader('Content-Type'));
    $this->assertTitle('Test active link class | Drupal');
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);

    $this->drupalGet('common-test/type-link-active-class', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'json']]);
    $this->assertIdentical('application/json', $this->drupalGetHeader('Content-Type'));
    $json = Json::decode($this->getRawContent());
    $this->assertEqual(['content', 'title'], array_keys($json));
    $this->assertIdentical('Test active link class', $json['title']);
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);
  }

  /**
   * Tests rendering form elements without passing through
   * \Drupal::formBuilder()->doBuildForm().
   */
  public function testDrupalRenderFormElements() {
    // Define a series of form elements.
    $element = [
      '#type' => 'button',
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'submit']);

    $element = [
      '#type' => 'textfield',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'text']);

    $element = [
      '#type' => 'password',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'password']);

    $element = [
      '#type' => 'textarea',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//textarea');

    $element = [
      '#type' => 'radio',
      '#title' => $this->randomMachineName(),
      '#value' => FALSE,
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'radio']);

    $element = [
      '#type' => 'checkbox',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'checkbox']);

    $element = [
      '#type' => 'select',
      '#title' => $this->randomMachineName(),
      '#options' => [
        0 => $this->randomMachineName(),
        1 => $this->randomMachineName(),
      ],
    ];
    $this->assertRenderedElement($element, '//select');

    $element = [
      '#type' => 'file',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'file']);

    $element = [
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//div[contains(@class, :class) and contains(., :markup)]/label[contains(., :label)]', [
      ':class' => 'js-form-type-item',
      ':markup' => $element['#markup'],
      ':label' => $element['#title'],
    ]);

    $element = [
      '#type' => 'hidden',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'hidden']);

    $element = [
      '#type' => 'link',
      '#title' => $this->randomMachineName(),
      '#url' => Url::fromRoute('common_test.destination'),
      '#options' => [
        'absolute' => TRUE,
      ],
    ];
    $this->assertRenderedElement($element, '//a[@href=:href and contains(., :title)]', [
      ':href' => URL::fromRoute('common_test.destination')->setAbsolute()->toString(),
      ':title' => $element['#title'],
    ]);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details/summary[contains(., :title)]', [
      ':title' => $element['#title'],
    ]);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details');

    $element['item'] = [
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details/div/div[contains(@class, :class) and contains(., :markup)]', [
      ':class' => 'js-form-type-item',
      ':markup' => $element['item']['#markup'],
    ]);
  }

  /**
   * Tests that elements are rendered properly.
   */
  protected function assertRenderedElement(array $element, $xpath, array $xpath_args = []) {
    $original_element = $element;
    $this->setRawContent(drupal_render_root($element));
    $this->verbose('<hr />' . $this->getRawContent());

    // @see \Drupal\simpletest\WebTestBase::xpath()
    $xpath = $this->buildXPathQuery($xpath, $xpath_args);
    $element += ['#value' => NULL];
    $this->assertFieldByXPath($xpath, $element['#value'], format_string('#type @type was properly rendered.', [
      '@type' => var_export($element['#type'], TRUE),
    ]));
  }

}
