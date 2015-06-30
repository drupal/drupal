<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormBuilderTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
    $this->assertSame($form_arg, get_class($form_state->getFormObject()));
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
    $this->assertSame($form_arg, get_class($form_state->getFormObject()));
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
    $this->assertSame($form_arg, $form_state->getFormObject());
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
    $this->assertSame($form_arg, $form_state->getFormObject());
    $this->assertSame($base_form_id, $form_state->getBuildInfo()['base_form_id']);
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

    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_arg->expects($this->any())
      ->method('submitForm')
      ->will($this->returnCallback(function ($form, FormStateInterface $form_state) use ($response, $form_state_key) {
        $form_state->setFormState([$form_state_key => $response]);
      }));

    $form_state = new FormState();
    try {
      $input['form_id'] = $form_id;
      $form_state->setUserInput($input);
      $this->simulateFormSubmission($form_id, $form_arg, $form_state, FALSE);
      $this->fail('EnforcedResponseException was not thrown.');
    }
    catch (EnforcedResponseException $e) {
      $this->assertSame($response, $e->getResponse());
    }
    $this->assertSame($response, $form_state->getResponse());
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

    // Set up a redirect that will not be called.
    $redirect = $this->getMockBuilder('Symfony\Component\HttpFoundation\RedirectResponse')
      ->disableOriginalConstructor()
      ->getMock();

    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_arg->expects($this->any())
      ->method('submitForm')
      ->will($this->returnCallback(function ($form, FormStateInterface $form_state) use ($response, $redirect) {
        // Set both the response and the redirect.
        $form_state->setResponse($response);
        $form_state->set('redirect', $redirect);
      }));

    $form_state = new FormState();
    try {
      $input['form_id'] = $form_id;
      $form_state->setUserInput($input);
      $this->simulateFormSubmission($form_id, $form_arg, $form_state, FALSE);
      $this->fail('EnforcedResponseException was not thrown.');
    }
    catch (EnforcedResponseException $e) {
      $this->assertSame($response, $e->getResponse());
    }
    $this->assertSame($response, $form_state->getResponse());
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
    $this->assertSame('test-form-id', $form['#id']);
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
    $this->assertArrayHasKey('#id', $form);
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
    $this->assertSame('test-form', $form['#id']);
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
    $this->assertArrayHasKey('#id', $form);
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
    $this->assertSame('test-form', $form['#id']);
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
    $this->assertSame($form_id, $form_state->getBuildInfo()['form_id']);
    $this->assertArrayHasKey('#id', $form);
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
    $form_state->setRebuild();
    $input['form_id'] = $form_id;
    $form_state->setUserInput($input);
    $form_state->addRebuildInfo('copy', ['#build_id' => TRUE]);
    $this->formBuilder->processForm($form_id, $form, $form_state);
    $this->assertSame($original_build_id, $form['#build_id']);

    // Rebuild the form again, and assert that there is a new build ID.
    $form_state->setRebuildInfo([]);
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

    // Do an initial build of the form and track the build ID.
    $form_state = (new FormState())
      ->addBuildInfo('files', [['module' => 'node', 'type' => 'pages.inc']])
      ->setCached();
    $form = $this->formBuilder->buildForm($form_arg, $form_state);

    $cached_form = $form;
    $cached_form['#cache_token'] = 'csrf_token';
    // The form cache, form_state cache, and CSRF token validation will only be
    // called on the cached form.
    $this->formCache->expects($this->once())
      ->method('getCache')
      ->willReturn($form);

    // The final form build will not trigger any actual form building, but will
    // use the form cache.
    $form_state->setExecuted();
    $input['form_id'] = $form_id;
    $input['form_build_id'] = $form['#build_id'];
    $form_state->setUserInput($input);
    $this->formBuilder->buildForm($form_arg, $form_state);
    $this->assertEmpty($form_state->getErrors());
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

    $form_state = new FormState();
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame('test-form-id', $form['#id']);

    $form_state = new FormState();
    $form = $this->simulateFormSubmission($form_id, $form_arg, $form_state);
    $this->assertSame('test-form-id--2', $form['#id']);
  }

  /**
   * Tests that a cached form is deleted after submit.
   */
  public function testFormCacheDeletionCached() {
    $form_id = 'test_form_id';
    $form_build_id = $this->randomMachineName();

    $expected_form = $form_id();
    $expected_form['#build_id'] = $form_build_id;
    $form_arg = $this->getMockForm($form_id, $expected_form);
    $form_arg->expects($this->once())
      ->method('submitForm')
      ->willReturnCallback(function (array &$form, FormStateInterface $form_state) {
        // Mimic EntityForm by cleaning the $form_state upon submit.
        $form_state->cleanValues();
      });

    $this->formCache->expects($this->once())
      ->method('deleteCache')
      ->with($form_build_id);

    $form_state = new FormState();
    $form_state->setCached();
    $this->simulateFormSubmission($form_id, $form_arg, $form_state);
  }

  /**
   * Tests that an uncached form does not trigger cache set or delete.
   */
  public function testFormCacheDeletionUncached() {
    $form_id = 'test_form_id';
    $form_build_id = $this->randomMachineName();

    $expected_form = $form_id();
    $expected_form['#build_id'] = $form_build_id;
    $form_arg = $this->getMockForm($form_id, $expected_form);

    $this->formCache->expects($this->never())
      ->method('deleteCache');

    $form_state = new FormState();
    $this->simulateFormSubmission($form_id, $form_arg, $form_state);
  }

  /**
   * @covers ::buildForm
   *
   * @expectedException \Drupal\Core\Form\Exception\BrokenPostRequestException
   */
  public function testExceededFileSize() {
    $request = new Request([FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]);
    $request_stack = new RequestStack();
    $request_stack->push($request);
    $this->formBuilder = $this->getMockBuilder('\Drupal\Core\Form\FormBuilder')
      ->setConstructorArgs([$this->formValidator, $this->formSubmitter, $this->formCache, $this->moduleHandler, $this->eventDispatcher, $request_stack, $this->classResolver, $this->elementInfo, $this->themeManager, $this->csrfToken])
      ->setMethods(['getFileUploadMaxSize'])
      ->getMock();
    $this->formBuilder->expects($this->once())
      ->method('getFileUploadMaxSize')
      ->willReturn(33554432);

    $form_arg = $this->getMockForm('test_form_id');
    $form_state = new FormState();

    $this->formBuilder->buildForm($form_arg, $form_state);
  }

  /**
   * @covers ::buildForm
   *
   * @dataProvider providerTestChildAccessInheritance
   */
  public function testChildAccessInheritance($element, $access_checks) {
    $form_arg = new TestFormWithPredefinedForm();
    $form_arg->setForm($element);

    $form_state = new FormState();

    $form = $this->formBuilder->buildForm($form_arg, $form_state);

    $actual_access_structure = [];
    $expected_access_structure = [];

    // Ensure that the expected access checks are set.
    foreach ($access_checks as $access_check) {
      $parents = $access_check[0];
      $parents[] = '#access';

      $actual_access = NestedArray::getValue($form, $parents);
      $actual_access_structure[] = [$parents, $actual_access];
      $expected_access_structure[] = [$parents, $access_check[1]];
    }

    $this->assertEquals($expected_access_structure, $actual_access_structure);
  }

  /**
   * Data provider for testChildAccessInheritance.
   *
   * @return array
   */
  public function providerTestChildAccessInheritance() {
    $data = [];

    $element = [
      'child0' => [
        '#type' => 'checkbox',
      ],
      'child1' => [
        '#type' => 'checkbox',
      ],
      'child2' => [
        '#type' => 'fieldset',
        'child2.0' => [
          '#type' => 'checkbox',
        ],
        'child2.1' => [
          '#type' => 'checkbox',
        ],
        'child2.2' => [
          '#type' => 'checkbox',
        ],
      ],
    ];

    // Sets access FALSE on the root level, this should be inherited completely.
    $clone = $element;
    $clone['#access'] = FALSE;

    $expected_access = [];
    $expected_access[] = [[], FALSE];
    $expected_access[] = [['child0'], FALSE];
    $expected_access[] = [['child1'], FALSE];
    $expected_access[] = [['child2'], FALSE];
    $expected_access[] = [['child2', 'child2.0'], FALSE];
    $expected_access[] = [['child2', 'child2.1'], FALSE];
    $expected_access[] = [['child2', 'child2.2'], FALSE];

    $data['access-false-root'] = [$clone, $expected_access];

    $clone = $element;
    $access_result = AccessResult::forbidden()->addCacheContexts(['user']);
    $clone['#access'] = $access_result;

    $expected_access = [];
    $expected_access[] = [[], $access_result];
    $expected_access[] = [['child0'], $access_result];
    $expected_access[] = [['child1'], $access_result];
    $expected_access[] = [['child2'], $access_result];
    $expected_access[] = [['child2', 'child2.0'], $access_result];
    $expected_access[] = [['child2', 'child2.1'], $access_result];
    $expected_access[] = [['child2', 'child2.2'], $access_result];

    $data['access-forbidden-root'] = [$clone, $expected_access];

    // Allow access on the most outer level but set FALSE otherwise.
    $clone = $element;
    $clone['#access'] = TRUE;
    $clone['child0']['#access'] = FALSE;

    $expected_access = [];
    $expected_access[] = [[], TRUE];
    $expected_access[] = [['child0'], FALSE];
    $expected_access[] = [['child1'], NULL];
    $expected_access[] = [['child2'], NULL];
    $expected_access[] = [['child2', 'child2.0'], NULL];
    $expected_access[] = [['child2', 'child2.1'], NULL];
    $expected_access[] = [['child2', 'child2.2'], NULL];

    $data['access-true-root'] = [$clone, $expected_access];

    // Allow access on the most outer level but forbid otherwise.
    $clone = $element;
    $access_result_allowed = AccessResult::allowed()
      ->addCacheContexts(['user']);
    $clone['#access'] = $access_result_allowed;
    $access_result_forbidden = AccessResult::forbidden()
      ->addCacheContexts(['user']);
    $clone['child0']['#access'] = $access_result_forbidden;

    $expected_access = [];
    $expected_access[] = [[], $access_result_allowed];
    $expected_access[] = [['child0'], $access_result_forbidden];
    $expected_access[] = [['child1'], NULL];
    $expected_access[] = [['child2'], NULL];
    $expected_access[] = [['child2', 'child2.0'], NULL];
    $expected_access[] = [['child2', 'child2.1'], NULL];
    $expected_access[] = [['child2', 'child2.2'], NULL];

    $data['access-allowed-root'] = [$clone, $expected_access];

    // Allow access on the most outer level, deny access on a parent, and allow
    // on a child. The denying should be inherited.
    $clone = $element;
    $clone['#access'] = TRUE;
    $clone['child2']['#access'] = FALSE;
    $clone['child2.0']['#access'] = TRUE;
    $clone['child2.1']['#access'] = TRUE;
    $clone['child2.2']['#access'] = TRUE;

    $expected_access = [];
    $expected_access[] = [[], TRUE];
    $expected_access[] = [['child0'], NULL];
    $expected_access[] = [['child1'], NULL];
    $expected_access[] = [['child2'], FALSE];
    $expected_access[] = [['child2', 'child2.0'], FALSE];
    $expected_access[] = [['child2', 'child2.1'], FALSE];
    $expected_access[] = [['child2', 'child2.2'], FALSE];

    $data['access-mixed-parents'] = [$clone, $expected_access];

    $clone = $element;
    $clone['#access'] = $access_result_allowed;
    $clone['child2']['#access'] = $access_result_forbidden;
    $clone['child2.0']['#access'] = $access_result_allowed;
    $clone['child2.1']['#access'] = $access_result_allowed;
    $clone['child2.2']['#access'] = $access_result_allowed;

    $expected_access = [];
    $expected_access[] = [[], $access_result_allowed];
    $expected_access[] = [['child0'], NULL];
    $expected_access[] = [['child1'], NULL];
    $expected_access[] = [['child2'], $access_result_forbidden];
    $expected_access[] = [['child2', 'child2.0'], $access_result_forbidden];
    $expected_access[] = [['child2', 'child2.1'], $access_result_forbidden];
    $expected_access[] = [['child2', 'child2.2'], $access_result_forbidden];

    $data['access-mixed-parents-object'] = [$clone, $expected_access];

    return $data;
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


class TestFormWithPredefinedForm extends TestForm {

  /**
   * @var array
   */
  protected $form;

  public function setForm($form) {
    $this->form = $form;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->form;
  }

}
