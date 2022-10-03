<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormValidator;
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
   * @var \Drupal\Core\Form\FormValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formValidator;

  /**
   * @var \Drupal\Core\Form\FormSubmitterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formSubmitter;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The form cache.
   *
   * @var \Drupal\Core\Form\FormCacheInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formCache;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $translationManager;

  /**
   * @var \Drupal\Core\DrupalKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $kernel;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add functions to the global namespace for testing.
    require_once __DIR__ . '/fixtures/form_base_test.inc';

    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->formCache = $this->createMock('Drupal\Core\Form\FormCacheInterface');
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->urlGenerator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $this->classResolver = $this->getClassResolverStub();

    $this->elementInfo = $this->getMockBuilder('\Drupal\Core\Render\ElementInfoManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->elementInfo->expects($this->any())
      ->method('getInfo')
      ->willReturnCallback([$this, 'getInfo']);

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->kernel = $this->getMockBuilder('\Drupal\Core\DrupalKernel')
      ->disableOriginalConstructor()
      ->getMock();
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->themeManager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');
    $this->request = Request::createFromGlobals();
    $this->eventDispatcher = $this->createMock('Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);
    $this->logger = $this->createMock('Drupal\Core\Logger\LoggerChannelInterface');
    $form_error_handler = $this->createMock('Drupal\Core\Form\FormErrorHandlerInterface');
    $this->formValidator = new FormValidator($this->requestStack, $this->getStringTranslationStub(), $this->csrfToken, $this->logger, $form_error_handler);
    $this->formSubmitter = $this->getMockBuilder('Drupal\Core\Form\FormSubmitter')
      ->setConstructorArgs([$this->requestStack, $this->urlGenerator])
      ->onlyMethods(['batchGet'])
      ->getMock();
    $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);

    $this->formBuilder = new FormBuilder($this->formValidator, $this->formSubmitter, $this->formCache, $this->moduleHandler, $this->eventDispatcher, $this->requestStack, $this->classResolver, $this->elementInfo, $this->themeManager, $this->csrfToken);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
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
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Form\FormInterface
   *   The mocked form object.
   */
  protected function getMockForm($form_id, $expected_form = NULL, $count = 1) {
    $form = $this->createMock('Drupal\Core\Form\FormInterface');
    $form->expects($this->once())
      ->method('getFormId')
      ->willReturn($form_id);

    if ($expected_form) {
      $form->expects($this->exactly($count))
        ->method('buildForm')
        ->willReturn($expected_form);
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
