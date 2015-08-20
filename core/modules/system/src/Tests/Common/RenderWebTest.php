<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\RenderWebTest.
 */

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
  public static $modules = array('common_test');

  /**
   * Asserts the cache context for the wrapper format is always present.
   */
  function testWrapperFormatCacheContext() {
    $this->drupalGet('');
    $this->assertIdentical(0, strpos($this->getRawContent(), "<!DOCTYPE html>\n<html"));
    $this->assertIdentical('text/html; charset=UTF-8', $this->drupalGetHeader('Content-Type'));
    $this->assertTitle('Log in | Drupal');
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);

    $this->drupalGet('', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT =>  'json']]);
    $this->assertIdentical('application/json', $this->drupalGetHeader('Content-Type'));
    $json = Json::decode($this->getRawContent());
    $this->assertEqual(['content', 'title'], array_keys($json));
    $this->assertIdentical('Log in', $json['title']);
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);
  }

  /**
   * Tests rendering form elements without passing through
   * \Drupal::formBuilder()->doBuildForm().
   */
  function testDrupalRenderFormElements() {
    // Define a series of form elements.
    $element = array(
      '#type' => 'button',
      '#value' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'submit'));

    $element = array(
      '#type' => 'textfield',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'text'));

    $element = array(
      '#type' => 'password',
      '#title' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'password'));

    $element = array(
      '#type' => 'textarea',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//textarea');

    $element = array(
      '#type' => 'radio',
      '#title' => $this->randomMachineName(),
      '#value' => FALSE,
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'radio'));

    $element = array(
      '#type' => 'checkbox',
      '#title' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'checkbox'));

    $element = array(
      '#type' => 'select',
      '#title' => $this->randomMachineName(),
      '#options' => array(
        0 => $this->randomMachineName(),
        1 => $this->randomMachineName(),
      ),
    );
    $this->assertRenderedElement($element, '//select');

    $element = array(
      '#type' => 'file',
      '#title' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'file'));

    $element = array(
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//div[contains(@class, :class) and contains(., :markup)]/label[contains(., :label)]', array(
      ':class' => 'js-form-type-item',
      ':markup' => $element['#markup'],
      ':label' => $element['#title'],
    ));

    $element = array(
      '#type' => 'hidden',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//input[@type=:type]', array(':type' => 'hidden'));

    $element = array(
      '#type' => 'link',
      '#title' => $this->randomMachineName(),
      '#url' => Url::fromRoute('common_test.destination'),
      '#options' => array(
        'absolute' => TRUE,
      ),
    );
    $this->assertRenderedElement($element, '//a[@href=:href and contains(., :title)]', array(
      ':href' => \Drupal::urlGenerator()->generateFromPath('common-test/destination', ['absolute' => TRUE]),
      ':title' => $element['#title'],
    ));

    $element = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//details/summary[contains(., :title)]', array(
      ':title' => $element['#title'],
    ));

    $element = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//details');

    $element['item'] = array(
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    );
    $this->assertRenderedElement($element, '//details/div/div[contains(@class, :class) and contains(., :markup)]', array(
      ':class' => 'js-form-type-item',
      ':markup' => $element['item']['#markup'],
    ));
  }

  /**
   * Tests that elements are rendered properly.
   */
  protected function assertRenderedElement(array $element, $xpath, array $xpath_args = array()) {
    $original_element = $element;
    $this->setRawContent(drupal_render_root($element));
    $this->verbose('<hr />' . $this->getRawContent());

    // @see \Drupal\simpletest\WebTestBase::xpath()
    $xpath = $this->buildXPathQuery($xpath, $xpath_args);
    $element += array('#value' => NULL);
    $this->assertFieldByXPath($xpath, $element['#value'], format_string('#type @type was properly rendered.', array(
      '@type' => var_export($element['#type'], TRUE),
    )));
  }

}
