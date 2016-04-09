<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormHelper
 * @group Form
 */
class FormHelperTest extends UnitTestCase {

  /**
   * Tests rewriting the #states selectors.
   *
   * @covers ::rewriteStatesSelector
   */
  function testRewriteStatesSelector() {

    // Simple selectors.
    $value = array('value' => 'medium');
    $form['foo']['#states'] = array(
      'visible' => array(
        'select[name="fields[foo-id][settings_edit_form][settings][image_style]"]' => $value,
      ),
    );
    FormHelper::rewriteStatesSelector($form, 'fields[foo-id][settings_edit_form]', 'options');
    $expected_selector = 'select[name="options[settings][image_style]"]';
    $this->assertSame($form['foo']['#states']['visible'][$expected_selector], $value, 'The #states selector was not properly rewritten.');

    // Complex selectors.
    $form = array();
    $form['bar']['#states'] = array(
      'visible' => array(
        array(
          ':input[name="menu[type]"]' => array('value' => 'normal'),
        ),
        array(
          ':input[name="menu[type]"]' => array('value' => 'tab'),
        ),
        ':input[name="menu[type]"]' => array('value' => 'default tab'),
      ),
      // Example from https://www.drupal.org/node/1464758
      'disabled' => array(
        '[name="menu[options][dependee_1]"]' => array('value' => 'ON'),
        array(
          array('[name="menu[options][dependee_2]"]' => array('value' => 'ON')),
          array('[name="menu[options][dependee_3]"]' => array('value' => 'ON')),
        ),
        array(
          array('[name="menu[options][dependee_4]"]' => array('value' => 'ON')),
          'xor',
          array('[name="menu[options][dependee_5]"]' => array('value' => 'ON')),
        ),
      ),
    );
    $expected['bar']['#states'] = array(
      'visible' => array(
        array(
          ':input[name="options[type]"]' => array('value' => 'normal'),
        ),
        array(
          ':input[name="options[type]"]' => array('value' => 'tab'),
        ),
        ':input[name="options[type]"]' => array('value' => 'default tab'),
      ),
      'disabled' => array(
        '[name="options[options][dependee_1]"]' => array('value' => 'ON'),
        array(
          array('[name="options[options][dependee_2]"]' => array('value' => 'ON')),
          array('[name="options[options][dependee_3]"]' => array('value' => 'ON')),
        ),
        array(
          array('[name="options[options][dependee_4]"]' => array('value' => 'ON')),
          'xor',
          array('[name="options[options][dependee_5]"]' => array('value' => 'ON')),
        ),
      ),
    );
    FormHelper::rewriteStatesSelector($form, 'menu', 'options');
    $this->assertSame($expected, $form, 'The #states selectors were properly rewritten.');
  }

}
