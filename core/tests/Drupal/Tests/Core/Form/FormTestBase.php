<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a base class for testing form functionality.
 *
 * @see \Drupal\Core\Form\FormBuilder
 */
abstract class FormTestBase extends UnitTestCase {

  /**
   * The form builder being tested.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\Core\Form\FormValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formValidator;

  /**
   * @var \Drupal\Core\Form\FormSubmitterInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formSubmitter;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The form cache.
   *
   * @var \Drupal\Core\Form\FormCacheInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formCache;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The class results.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $classResolver;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * @var \Drupal\Core\DrupalKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $kernel;

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add functions to the global namespace for testing.
    require_once __DIR__ . '/fixtures/form_base_test.inc';

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCache = $this->getMock('Drupal\Core\Form\FormCacheInterface');
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $this->classResolver = $this->getClassResolverStub();

    $this->elementInfo = $this->getMockBuilder('\Drupal\Core\Render\ElementInfoManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->will($this->returnCallback([$this, 'getInfo']));

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->kernel = $this->getMockBuilder('\Drupal\Core\DrupalKernel')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->themeManager = $this->getMock('Drupal\Core\Theme\ThemeManagerInterface');
    $this->request = new Request();
    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);
    $this->logger = $this->getMock('Drupal\Core\Logger\LoggerChannelInterface');
    $form_error_handler = $this->getMock('Drupal\Core\Form\FormErrorHandlerInterface');
    $this->formValidator = $this->getMockBuilder('Drupal\Core\Form\FormValidator')
      ->setConstructorArgs([$this->requestStack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $form_error_handler])
      ->setMethods(NULL)
      ->getMock();
    $this->formSubmitter = $this->getMockBuilder('Drupal\Core\Form\FormSubmitter')
      ->setConstructorArgs([$this->requestStack, $this->urlGenerator])
      ->setMethods(['batchGet', 'drupalInstallationAttempted'])
      ->getMock();
    $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));

    $this->formBuilder = new FormBuilder($this->formValidator, $this->formSubmitter, $this->formCache, $this->moduleHandler, $this->eventDispatcher, $this->requestStack, $this->classResolver, $this->elementInfo, $this->themeManager, $this->csrfToken);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    Html::resetSeenIds();
    (new FormState())->clearErrors();
  }

  /**
   * Provides a mocked form object.
   *
   * @param string $form_id
   *   The form ID to be used.
   * @param mixed $expected_form
   *   (optional) If provided, the expected form response for buildForm() to
   *   return. Defaults to NULL.
   * @param int $count
   *   (optional) The number of times the form is expected to be built. Defaults
   *   to 1.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Form\FormInterface
   *   The mocked form object.
   */
  protected function getMockForm($form_id, $expected_form = NULL, $count = 1) {
    $form = $this->getMock('Drupal\Core\Form\FormInterface');
    $form->expects($this->once())
      ->method('getFormId')
      ->will($this->returnValue($form_id));

    if ($expected_form) {
      $form->expects($this->exactly($count))
        ->method('buildForm')
        ->will($this->returnValue($expected_form));
    }
    return $form;
  }

  /**
   * Simulates a form submission within a request, bypassing submitForm().
   *
   * Calling submitForm() will reset the form builder, if two forms were on the
   * same page, they will be submitted simultaneously.
   *
   * @param string $form_id
   *   The unique string identifying the form.
   * @param \Drupal\Core\Form\FormInterface $form_arg
   *   The form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $programmed
   *   Whether $form_state->setProgrammed() should be passed TRUE or not. If it
   *   is not set to TRUE, you must provide additional data in $form_state for
   *   the submission to take place.
   *
   * @return array
   *   The built form.
   */
  protected function simulateFormSubmission($form_id, FormInterface $form_arg, FormStateInterface $form_state, $programmed = TRUE) {
    $input = $form_state->getUserInput();
    $input['op'] = 'Submit';
    $form_state
      ->setUserInput($input)
      ->setProgrammed($programmed)
      ->setSubmitted();
    return $this->formBuilder->buildForm($form_arg, $form_state);
  }

  /**
   * Asserts that the expected form structure is found in a form for a given key.
   *
   * @param array $expected_form
   *   The expected form structure.
   * @param array $actual_form
   *   The actual form.
   * @param string|null $form_key
   *   (optional) The form key to look in. Otherwise the entire form will be
   *   compared.
   */
  protected function assertFormElement(array $expected_form, array $actual_form, $form_key = NULL) {
    $expected_element = $form_key ? $expected_form[$form_key] : $expected_form;
    $actual_element = $form_key ? $actual_form[$form_key] : $actual_form;
    $this->assertSame(array_intersect_key($expected_element, $actual_element), $expected_element);
  }

  /**
   * A stub method returning properties for the defined element type.
   *
   * @param string $type
   *   The machine name of an element type plugin.
   *
   * @return array
   *   An array with dummy values to be used in tests. Defaults to an empty
   *   array.
   */
  public function getInfo($type) {
    $types['hidden'] = [
      '#input' => TRUE,
    ];
    $types['token'] = [
      '#input' => TRUE,
    ];
    $types['value'] = [
      '#input' => TRUE,
    ];
    $types['radios'] = [
      '#input' => TRUE,
    ];
    $types['textfield'] = [
      '#input' => TRUE,
    ];
    $types['submit'] = [
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
    ];
    if (!isset($types[$type])) {
      $types[$type] = [];
    }
    return $types[$type];
  }

}
