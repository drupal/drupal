<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormBuilderTest.
 */

namespace Drupal\Tests\Core\Form {

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormBuilder
 * @group Form
 */
class FormBuilderTest extends FormTestBase {

  /**
   * Tests the getFormId() method with a string based form ID.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The form argument foo is not a valid form.
   */
  public function testGetFormIdWithString() {
    $form_arg = 'foo';

    $clean_form_state = new FormState();
    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($form_arg, $form_id);
    $this->assertSame($clean_form_state, $form_state);
  }

  /**
   * Tests the getFormId() method with a class name form ID.
   */
  public function testGetFormIdWithClassName() {
    $form_arg = 'Drupal\Tests\Core\Form\TestForm';

    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame('test_form', $form_id);
    $this->assertSame($form_arg, get_class($form_state['build_info']['callback_object']));
  }

  /**
   * Tests the getFormId() method with an injected class name form ID.
   */
  public function testGetFormIdWithInjectedClassName() {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    \Drupal::setContainer($container);

    $form_arg = 'Drupal\Tests\Core\Form\TestFormInjected';

    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame('test_form', $form_id);
    $this->assertSame($form_arg, get_class($form_state['build_info']['callback_object']));
  }

  /**
   * Tests the getFormId() method with a form object.
   */
  public function testGetFormIdWithObject() {
    $expected_form_id = 'my_module_form_id';

    $form_arg = $this->getMockForm($expected_form_id);

    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($expected_form_id, $form_id);
    $this->assertSame($form_arg, $form_state['build_info']['callback_object']);
  }

  /**
   * Tests the getFormId() method with a base form object.
   */
  public function testGetFormIdWithBaseForm() {
    $expected_form_id = 'my_module_form_id';
    $base_form_id = 'my_module';

    $form_arg = $this->getMock('Drupal\Core\Form\BaseFormIdInterface');
    $form_arg->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($expected_form_id));
    $form_arg->expects($this->once())
      ->method('getBaseFormId')
      ->will($this->returnValue($base_form_id));

    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($form_arg, $form_state);

    $this->assertSame($expected_form_id, $form_id);
    $this->assertSame($form_arg, $form_state['build_info']['callback_object']);
    $this->assertSame($base_form_id, $form_state['build_info']['base_form_id']);
  }

  /**
   * Tests the handling of FormStateInterface::$response.
   *
   * @dataProvider formStateResponseProvider
   */
  public function testHandleFormStateResponse($class, $form_state_key) {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $response = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->getMock();
    $response->expects($this->any())
      ->method('prepare')
      ->will($this->returnValue($response));

    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_arg->expects($this->any())
      ->method('submitForm')
      ->will($this->returnCallback(function ($form, FormStateInterface $form_state) use ($response, $form_state_key) {
        $form_state[$form_state_key] = $response;
      }));

    $form_state = new FormState();
    try {
      $form_state['values'] = array();
      $form_state['input']['form_id'] = $form_id;
      $this->simulateFormSubmission($form_id, $form_arg, $form_state, FALSE);
      $this->fail('TestFormBuilder::sendResponse() was not triggered.');
    }
    catch (\Exception $e) {
      $this->assertSame('exit', $e->getMessage());
    }
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $form_state->get('response'));
  }

  /**
   * Provides test data for testHandleFormStateResponse().
   */
  public function formStateResponseProvider() {
    return array(
      array('Symfony\Component\HttpFoundation\Response', 'response'),
      array('Symfony\Component\HttpFoundation\RedirectResponse', 'redirect'),
    );
  }

  /**
   * Tests the handling of a redirect when FormStateInterface::$response exists.
   */
  public function testHandleRedirectWithResponse() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    // Set up a response that will be used.
    $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response->expects($this->once())
      ->method('prepare')
      ->will($this->returnValue($response));

    // Set up a redirect that will not be called.
    $redirect = $this->getMockBuilder('Symfony\Component\HttpFoundation\RedirectResponse')
      ->disableOriginalConstructor()
      ->getMock();
    $redirect->expects($this->never())
      ->method('prepare');

    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_arg->expects($this->any())
      ->method('submitForm')
      ->will($this->returnCallback(function ($form, FormStateInterface $form_state) use ($response, $redirect) {
        // Set both the response and the redirect.
        $form_state->setResponse($response);
        $form_state['redirect'] = $redirect;
      }));

    $form_state = new FormState();
    try {
      $form_state['values'] = array();
      $form_state['input']['form_id'] = $form_id;
      $this->simulateFormSubmission($form_id, $form_arg, $form_state, FALSE);
      $this->fail('TestFormBuilder::sendResponse() was not triggered.');
    }
    catch (\Exception $e) {
      $this->assertSame('exit', $e->getMessage());
    }
    $this->assertSame($response, $form_state->get('response'));
  }

  /**
   * Tests the getForm() method with a string based form ID.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The form argument test_form_id is not a valid form.
   */
  public function testGetFormWithString() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the getForm() method with a form object.
   */
  public function testGetFormWithObject() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form_arg = $this->getMockForm($form_id, $expected_form);

    $form = $this->formBuilder->getForm($form_arg);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the getForm() method with a class name based form ID.
   */
  public function testGetFormWithClassString() {
    $form_id = '\Drupal\Tests\Core\Form\TestForm';
    $object = new TestForm();
    $form = array();
    $form_state = new FormState();
    $expected_form = $object->buildForm($form, $form_state);

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame('test_form', $form['#id']);
  }

  /**
   * Tests the buildForm() method with a string based form ID.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The form argument test_form_id is not a valid form.
   */
  public function testBuildFormWithString() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form = $this->formBuilder->getForm($form_id);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the buildForm() method with a class name based form ID.
   */
  public function testBuildFormWithClassString() {
    $form_id = '\Drupal\Tests\Core\Form\TestForm';
    $object = new TestForm();
    $form = array();
    $form_state = new FormState();
    $expected_form = $object->buildForm($form, $form_state);

    $form = $this->formBuilder->buildForm($form_id, $form_state);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame('test_form', $form['#id']);
  }

  /**
   * Tests the buildForm() method with a form object.
   */
  public function testBuildFormWithObject() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    $form_arg = $this->getMockForm($form_id, $expected_form);

    $form_state = new FormState();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertFormElement($expected_form, $form, 'test');
    $this->assertSame($form_id, $form_state['build_info']['form_id']);
    $this->assertSame($form_id, $form['#id']);
  }

  /**
   * Tests the rebuildForm() method.
   */
  public function testRebuildForm() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();

    // The form will be built four times.
    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(2))
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->exactly(4))
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // Do an initial build of the form and track the build ID.
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $original_build_id = $form['#build_id'];

    // Rebuild the form, and assert that the build ID has not changed.
    $form_state['rebuild'] = TRUE;
    $form_state['input']['form_id'] = $form_id;
    $form_state['rebuild_info']['copy']['#build_id'] = TRUE;
    $this->formBuilder->processForm($form_id, $form, $form_state);
    $this->assertSame($original_build_id, $form['#build_id']);

    // Rebuild the form again, and assert that there is a new build ID.
    $form_state['rebuild_info'] = array();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertNotSame($original_build_id, $form['#build_id']);
  }

  /**
   * Tests the getCache() method.
   */
  public function testGetCache() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['#token'] = FALSE;

    // FormBuilder::buildForm() will be called twice, but the form object will
    // only be called once due to caching.
    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(2))
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->once())
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    // The CSRF token is checked each time.
    $this->csrfToken->expects($this->exactly(2))
      ->method('get')
      ->will($this->returnValue('csrf_token'));
    // The CSRF token is validated only when retrieving from the cache.
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with('csrf_token')
      ->will($this->returnValue(TRUE));
    // The user is checked for authentication once for the form building and
    // twice for each cache set.
    $this->account->expects($this->exactly(3))
      ->method('isAuthenticated')
      ->will($this->returnValue(TRUE));

    // Do an initial build of the form and track the build ID.
    $form_state = new FormState();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['files'] = array(array('module' => 'node', 'type' => 'pages.inc'));
    $form_state['cache'] = TRUE;
    $form = $this->formBuilder->buildForm($form_arg, $form_state);

    $cached_form = $form;
    $cached_form['#cache_token'] = 'csrf_token';
    // The form cache, form_state cache, and CSRF token validation will only be
    // called on the cached form.
    $this->formCache->expects($this->once())
      ->method('setWithExpire');
    $this->formCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($cached_form));
    $this->formStateCache->expects($this->once())
      ->method('get')
      ->will($this->returnValue($form_state->getCacheableArray()));

    // The final form build will not trigger any actual form building, but will
    // use the form cache.
    $form_state['executed'] = TRUE;
    $form_state['input']['form_id'] = $form_id;
    $form_state['input']['form_build_id'] = $form['#build_id'];
    $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertEmpty($form_state['errors']);
  }

  /**
   * Tests the sendResponse() method.
   *
   * @expectedException \Exception
   */
  public function testSendResponse() {
    $form_id = 'test_form_id';
    $expected_form = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $expected_form->expects($this->once())
      ->method('prepare')
      ->will($this->returnValue($expected_form));

    $form_arg = $this->getMockForm($form_id, $expected_form);

    // Do an initial build of the form and track the build ID.
    $form_state = new FormState();
    $this->formBuilder->buildForm($form_arg, $form_state);
  }

  /**
   * Tests that HTML IDs are unique when rebuilding a form with errors.
   */
  public function testUniqueHtmlId() {
    $form_id = 'test_form_id';
    $expected_form = $form_id();
    $expected_form['test']['#required'] = TRUE;

    // Mock a form object that will be built two times.
    $form_arg = $this->getMock('Drupal\Core\Form\FormInterface');
    $form_arg->expects($this->exactly(2))
      ->method('getFormId')
      ->will($this->returnValue($form_id));
    $form_arg->expects($this->exactly(2))
      ->method('buildForm')
      ->will($this->returnValue($expected_form));

    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame($form_id, $form['#id']);

    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->setMethods(array('drupalSetMessage'))
      ->getMock();
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame("$form_id--2", $form['#id']);
  }

}

class TestForm implements FormInterface {
  public function getFormId() {
    return 'test_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    return test_form_id();
  }
  public function validateForm(array &$form, FormStateInterface $form_state) { }
  public function submitForm(array &$form, FormStateInterface $form_state) { }
}
class TestFormInjected extends TestForm implements ContainerInjectionInterface {
  public static function create(ContainerInterface $container) {
    return new static();
  }
}

}

namespace {
  use Drupal\Core\Form\FormStateInterface;

  function test_form_id_custom_submit(array &$form, FormStateInterface $form_state) {
  }
  // @todo Remove once watchdog() is removed.
  if (!defined('WATCHDOG_ERROR')) {
    define('WATCHDOG_ERROR', 3);
  }
}
