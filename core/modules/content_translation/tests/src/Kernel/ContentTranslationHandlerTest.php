<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the content translation handler.
 *
 * @group content_translation
 *
 * @coversDefaultClass \Drupal\content_translation\ContentTranslationHandler
 */
class ContentTranslationHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'entity_test',
    'language',
    'user',
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The ID of the entity type used in this test.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test_mul';

  /**
   * The ID of the translation language used in this test.
   *
   * @var string
   */
  protected $translationLangcode = 'af';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->state = $this->container->get('state');
    $this->entityTypeBundleInfo = $this->container->get('entity_type.bundle.info');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->messenger = $this->container->get('messenger');

    $this->installEntitySchema($this->entityTypeId);
    ConfigurableLanguage::createFromLangcode($this->translationLangcode)->save();
  }

  /**
   * Tests ContentTranslationHandler::entityFormSharedElements()
   *
   * @param array $element
   *   The element that will be altered.
   * @param bool $default_translation_affected
   *   Whether or not only the default translation of the entity is affected.
   * @param bool $default_translation
   *   Whether or not the entity is the default translation.
   * @param bool $translation_form
   *   Whether or not the form is a translation form.
   * @param array $expected
   *   The expected altered element.
   *
   * @dataProvider providerTestEntityFormSharedElements
   *
   * @covers ::entityFormSharedElements
   * @covers ::addTranslatabilityClue
   */
  public function testEntityFormSharedElements(array $element, $default_translation_affected, $default_translation, $translation_form, $is_submitted, $is_rebuilding, array $expected, $display_warning) {
    $this->state->set('entity_test.translation', TRUE);
    $this->state->set('entity_test.untranslatable_fields.default_translation_affected', $default_translation_affected);
    $this->entityTypeBundleInfo->clearCachedBundles();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($this->entityTypeId)->create();
    if (!$default_translation) {
      $entity = $entity->addTranslation($this->translationLangcode);
    }
    $entity->save();

    $form_object = $this->entityTypeManager->getFormObject($this->entityTypeId, 'default');
    $form_object->setEntity($entity);

    $form_state = new FormState();
    $form_state
      ->addBuildInfo('callback_object', $form_object)
      ->set(['content_translation', 'translation_form'], $translation_form);
    if ($is_submitted) {
      $form_state->setSubmitted();
    }
    $form_state->setRebuild($is_rebuilding);

    $handler = $this->entityTypeManager->getHandler($this->entityTypeId, 'translation');
    $actual = $handler->entityFormSharedElements($element, $form_state, $element);

    $this->assertEquals($expected, $actual);
    if ($display_warning) {
      $messages = $this->messenger->messagesByType('warning');
      $this->assertCount(1, $messages);
      $expected_message = sprintf('Fields that apply to all languages are hidden to avoid conflicting changes. <a href="%s">Edit them on the original language form</a>.', $entity->toUrl('edit-form')->toString());
      $this->assertEquals($expected_message, reset($messages));
    }
  }

  /**
   * Returns test cases for ::testEntityFormSharedElements().
   *
   * @return array[]
   *   An array of test cases, each one containing the element to alter, the
   *   form state, and the expected altered element.
   */
  public function providerTestEntityFormSharedElements() {
    $tests = [];

    $element = [];
    $tests['empty'] = [
      'element' => $element,
      'default_translation_affected' => TRUE,
      'default_translation' => TRUE,
      'translation_form' => FALSE,
      'is_submitted' => TRUE,
      'is_rebuilding' => TRUE,
      'expected' => $element,
      'display_warning' => FALSE,
    ];

    $element = [
      '#type' => 'textfield',
    ];
    $tests['no-children'] = $tests['empty'];
    $tests['no-children']['element'] = $element;
    $tests['no-children']['expected'] = $element;

    $element = [
      'test' => [
        '#type' => 'textfield',
        '#multilingual' => TRUE,
      ],
    ];
    $tests['multilingual'] = $tests['empty'];
    $tests['multilingual']['element'] = $element;
    $tests['multilingual']['expected'] = $element;

    unset($element['test']['#multilingual']);
    $tests['no-title'] = $tests['empty'];
    $tests['no-title']['element'] = $element;
    $tests['no-title']['expected'] = $element;

    $element['test']['#title'] = 'Test';
    $tests['no-translatability-clue'] = $tests['empty'];
    $tests['no-translatability-clue']['element'] = $element;
    $tests['no-translatability-clue']['expected'] = $element;

    $expected = $element;
    $expected['test']['#title'] .= ' <span class="translation-entity-all-languages">(all languages)</span>';
    $tests['translatability-clue'] = $tests['no-translatability-clue'];
    $tests['translatability-clue']['default_translation_affected'] = FALSE;
    $tests['translatability-clue']['expected'] = $expected;

    $ignored_types = [
      'actions',
      'details',
      'hidden',
      'link',
      'token',
      'value',
      'vertical_tabs',
    ];
    foreach ($ignored_types as $ignored_type) {
      $element = [
        'test' => [
          '#type' => $ignored_type,
          '#title' => 'Test',
        ],
      ];
      $tests["ignore-$ignored_type"] = $tests['translatability-clue'];
      $tests["ignore-$ignored_type"]['element'] = $element;
      $tests["ignore-$ignored_type"]['expected'] = $element;
    }

    $tests['unknown-field'] = $tests['no-translatability-clue'];
    $tests['unknown-field']['default_translation'] = FALSE;

    $element = [
      'name' => [
        '#type' => 'textfield',
      ],
    ];
    $expected = $element;
    $expected['name']['#access'] = FALSE;
    $tests['hide-untranslatable'] = $tests['unknown-field'];
    $tests['hide-untranslatable']['element'] = $element;
    $tests['hide-untranslatable']['expected'] = $expected;

    $tests['is-rebuilding'] = $tests['hide-untranslatable'];
    $tests['is-rebuilding']['is_submitted'] = FALSE;

    $tests['display-warning'] = $tests['is-rebuilding'];
    $tests['display-warning']['is_rebuilding'] = FALSE;
    $tests['display-warning']['display_warning'] = TRUE;

    $tests['no-translation-form'] = $tests['no-translatability-clue'];
    $tests['no-translation-form']['translation_form'] = FALSE;

    return $tests;
  }

}
