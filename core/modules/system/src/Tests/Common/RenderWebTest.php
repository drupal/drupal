<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RenderWebTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\String;
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
   * Tests rendering form elements without passing through form_builder().
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
      ':class' => 'form-type-item',
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
      '#href' => $this->randomMachineName(),
      '#options' => array(
        'absolute' => TRUE,
      ),
    );
    $this->assertRenderedElement($element, '//a[@href=:href and contains(., :title)]', array(
      ':href' => url($element['#href'], array('absolute' => TRUE)),
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
      ':class' => 'form-type-item',
      ':markup' => $element['item']['#markup'],
    ));
  }

  /**
   * Tests that elements are rendered properly.
   */
  protected function assertRenderedElement(array $element, $xpath, array $xpath_args = array()) {
    $original_element = $element;
    $this->drupalSetContent(drupal_render($element));
    $this->verbose('<pre>' .  String::checkPlain(var_export($original_element, TRUE)) . '</pre>'
      . '<pre>' .  String::checkPlain(var_export($element, TRUE)) . '</pre>'
      . '<hr />' . $this->drupalGetContent()
    );

    // @see \Drupal\simpletest\WebTestBase::xpath()
    $xpath = $this->buildXPathQuery($xpath, $xpath_args);
    $element += array('#value' => NULL);
    $this->assertFieldByXPath($xpath, $element['#value'], format_string('#type @type was properly rendered.', array(
      '@type' => var_export($element['#type'], TRUE),
    )));
  }

}
