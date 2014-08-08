<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormStateTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormState
 *
 * @group Form
 */
class FormStateTest extends UnitTestCase {

  /**
   * Tests the getRedirect() method.
   *
   * @covers ::getRedirect
   *
   * @dataProvider providerTestGetRedirect
   */
  public function testGetRedirect($form_state_additions, $expected) {
    $form_state = new FormState($form_state_additions);
    $redirect = $form_state->getRedirect();
    $this->assertEquals($expected, $redirect);
  }

  /**
   * Provides test data for testing the getRedirect() method.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestGetRedirect() {
    $data = array();
    $data[] = array(array(), NULL);

    $redirect = new RedirectResponse('/example');
    $data[] = array(array('redirect' => $redirect), $redirect);

    $data[] = array(array('redirect' => new Url('test_route_b', array('key' => 'value'))), new Url('test_route_b', array('key' => 'value')));

    $data[] = array(array('programmed' => TRUE), NULL);
    $data[] = array(array('rebuild' => TRUE), NULL);
    $data[] = array(array('no_redirect' => TRUE), NULL);

    return $data;
  }

  /**
   * Tests the setError() method.
   *
   * @covers ::setError
   */
  public function testSetError() {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->expects($this->once())
      ->method('drupalSetMessage')
      ->willReturn('Fail');

    $element['#parents'] = array('foo', 'bar');
    $form_state->setError($element, 'Fail');
  }

  /**
   * Tests the getError() method.
   *
   * @covers ::getError
   *
   * @dataProvider providerTestGetError
   */
  public function testGetError($errors, $parents, $error = NULL) {
    $element['#parents'] = $parents;
    $form_state = new FormState(array(
      'errors' => $errors,
    ));
    $this->assertSame($error, $form_state->getError($element));
  }

  public function providerTestGetError() {
    return array(
      array(array(), array('foo')),
      array(array('foo][bar' => 'Fail'), array()),
      array(array('foo][bar' => 'Fail'), array('foo')),
      array(array('foo][bar' => 'Fail'), array('bar')),
      array(array('foo][bar' => 'Fail'), array('baz')),
      array(array('foo][bar' => 'Fail'), array('foo', 'bar'), 'Fail'),
      array(array('foo][bar' => 'Fail'), array('foo', 'bar', 'baz'), 'Fail'),
      array(array('foo][bar' => 'Fail 2'), array('foo')),
      array(array('foo' => 'Fail 1', 'foo][bar' => 'Fail 2'), array('foo'), 'Fail 1'),
      array(array('foo' => 'Fail 1', 'foo][bar' => 'Fail 2'), array('foo', 'bar'), 'Fail 1'),
    );
  }

  /**
   * @covers ::setErrorByName
   *
   * @dataProvider providerTestSetErrorByName
   */
  public function testSetErrorByName($limit_validation_errors, $expected_errors, $set_message = FALSE) {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setConstructorArgs(array(array('limit_validation_errors' => $limit_validation_errors)))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->clearErrors();
    $form_state->expects($set_message ? $this->once() : $this->never())
      ->method('drupalSetMessage');

    $form_state->setErrorByName('test', 'Fail 1');
    $form_state->setErrorByName('test', 'Fail 2');
    $form_state->setErrorByName('options');

    $this->assertSame(!empty($expected_errors), $form_state::hasAnyErrors());
    $this->assertSame($expected_errors, $form_state['errors']);
  }

  public function providerTestSetErrorByName() {
    return array(
      // Only validate the 'options' element.
      array(array(array('options')), array('options' => '')),
      // Do not limit an validation, and, ensuring the first error is returned
      // for the 'test' element.
      array(NULL, array('test' => 'Fail 1', 'options' => ''), TRUE),
      // Limit all validation.
      array(array(), array()),
    );
  }

  /**
   * Tests that form errors during submission throw an exception.
   *
   * @covers ::setErrorByName
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage Form errors cannot be set after form validation has finished.
   */
  public function testFormErrorsDuringSubmission() {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setConstructorArgs(array(array('validation_complete' => TRUE)))
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form_state->setErrorByName('test', 'message');
  }

  /**
   * Tests that setting the value for an element adds to the values.
   *
   * @covers ::setValueForElement
   */
  public function testSetValueForElement() {
    $element = array(
      '#parents' => array(
        'foo',
        'bar',
      ),
    );
    $value = $this->randomMachineName();

    $form_state = new FormState();
    $form_state->setValueForElement($element, $value);
    $expected = array(
      'foo' => array(
        'bar' => $value,
      ),
    );
    $this->assertSame($expected, $form_state->getValues());
  }

  /**
   * @covers ::getValue
   *
   * @dataProvider providerTestGetValue
   */
  public function testGetValue($key, $expected, $default = NULL) {
    $form_state = new FormState(array('values' => array(
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
    )));
    $this->assertSame($expected, $form_state->getValue($key, $default));
  }

  public function providerTestGetValue() {
    $data = array();
    $data[] = array(
      'foo', 'one',
    );
    $data[] = array(
      array('bar', 'baz'), 'two',
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), NULL,
    );
    $data[] = array(
      'baz', 'baz', 'baz',
    );
    return $data;
  }

  /**
   * @covers ::setValue
   *
   * @dataProvider providerTestSetValue
   */
  public function testSetValue($key, $value, $expected) {
    $form_state = new FormState(array('values' => array('bar' => 'wrong')));
    $form_state->setValue($key, $value);
    $this->assertSame($expected, $form_state->getValues());
  }

  public function providerTestSetValue() {
    $data = array();
    $data[] = array(
      'foo', 'one', array('bar' => 'wrong', 'foo' => 'one'),
    );
    $data[] = array(
      array('bar', 'baz'), 'two', array('bar' => array('baz' => 'two')),
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), NULL, array('bar' => 'wrong', 'foo' => array('bar' => array('baz' => NULL))),
    );
    return $data;
  }

  /**
   * @covers ::hasValue
   *
   * @dataProvider providerTestHasValue
   */
  public function testHasValue($key, $expected) {
    $form_state = new FormState(array('values' => array(
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    )));
    $this->assertSame($expected, $form_state->hasValue($key));
  }

  public function providerTestHasValue() {
    $data = array();
    $data[] = array(
      'foo', TRUE,
    );
    $data[] = array(
      array('bar', 'baz'), TRUE,
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), FALSE,
    );
    $data[] = array(
      'true', TRUE,
    );
    $data[] = array(
      'false', TRUE,
    );
    $data[] = array(
      'null', FALSE,
    );
    return $data;
  }

  /**
   * @covers ::isValueEmpty
   *
   * @dataProvider providerTestIsValueEmpty
   */
  public function testIsValueEmpty($key, $expected) {
    $form_state = new FormState(array('values' => array(
      'foo' => 'one',
      'bar' => array(
        'baz' => 'two',
      ),
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    )));
    $this->assertSame($expected, $form_state->isValueEmpty($key));
  }

  public function providerTestIsValueEmpty() {
    $data = array();
    $data[] = array(
      'foo', FALSE,
    );
    $data[] = array(
      array('bar', 'baz'), FALSE,
    );
    $data[] = array(
      array('foo', 'bar', 'baz'), TRUE,
    );
    $data[] = array(
      'true', FALSE,
    );
    $data[] = array(
      'false', TRUE,
    );
    $data[] = array(
      'null', TRUE,
    );
    return $data;
  }

}
