<?php

declare(strict_types = 1);

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RenderContext;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Form\EditorImageDialog;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests EditorImageDialog alteration to add image style selection.
 *
 * @see image_form_editor_image_dialog_alter()
 *
 * @group image
 */
class EditorImageStyleDialogTest extends EntityKernelTestBase {

  /**
   * Editor for testing.
   *
   * @var \Drupal\editor\EditorInterface
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'editor',
    'editor_test',
    'file',
    'image',
    'node',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['node']);

    // Install the image module config so we have the medium image style.
    $this->installConfig('image');

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();
    node_add_body_field($type);
    $this->installEntitySchema('user');
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Fixture to consolidate tasks while making filter status configurable.
   *
   * @param bool $enable_image_filter
   *   Whether to activate filter_image_style in the text format.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   The submitted form.
   */
  protected function setUpForm(bool $enable_image_filter) {
    $format = FilterFormat::create([
      'format' => $this->randomMachineName(),
      'name' => $this->randomString(),
      'weight' => 0,
      'filters' => [
        'filter_image_style' => ['status' => $enable_image_filter],
      ],
    ]);
    $format->save();

    // Set up text editor.
    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = Editor::create([
      'format' => $format->id(),
      'editor' => 'ckeditor',
      'image_upload' => [
        'max_size' => 100,
        'scheme' => 'public',
        'directory' => '',
        'status' => TRUE,
      ],
    ]);
    $editor->save();

    /** @var \Drupal\file\FileInterface $file */
    $file = file_save_data(file_get_contents($this->root . '/core/modules/image/sample.png'), 'public://');

    $input = [
      'editor_object' => [
        'src' => file_url_transform_relative($file->getFileUri()),
        'alt' => 'Balloons floating above a field.',
        'data-entity-type' => 'file',
        'data-entity-uuid' => $file->uuid(),
      ],
      'dialogOptions' => [
        'title' => 'Edit Image',
        'dialogClass' => 'editor-image-dialog',
        'autoResize' => 'true',
      ],
      '_drupal_ajax' => '1',
      'ajax_page_state' => [
        'theme' => 'bartik',
        'theme_token' => 'some-token',
        'libraries' => '',
      ],
    ];
    if ($enable_image_filter) {
      $input['editor_object']['data-image-style'] = 'medium';
    }

    $form_state = (new FormState())
      ->setRequestMethod('POST')
      ->setUserInput($input)
      ->addBuildInfo('args', [$editor]);

    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form_object = new EditorImageDialog(\Drupal::entityTypeManager()->getStorage('file'));
    $form_id = $form_builder->getFormId($form_object, $form_state);
    $form = [];

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $renderer->executeInRenderContext(new RenderContext(), function () use (&$form, $form_builder, $form_id, $form_state) {
      $form = $form_builder->retrieveForm($form_id, $form_state);
      $form_builder->prepareForm($form_id, $form, $form_state);
      $form_builder->processForm($form_id, $form, $form_state);
    });

    return $form;
  }

  /**
   * Tests that style selection is hidden when filter_image_style is disabled.
   */
  public function testDialogNoStyles(): void {
    $this->assertArrayNotHasKey('image_style', $this->setUpForm(FALSE));
  }

  /**
   * Tests EditorImageDialog when filter_image_style is enabled.
   */
  public function testDialogStyles(): void {
    $form = $this->setUpForm(TRUE);

    $this->assertSame(
      ['', 'large', 'medium', 'thumbnail', 'wide'],
      array_keys($form['image_style']['selection']['#options'])
    );
    $this->assertSame('medium', $form['image_style']['selection']['#default_value']);
  }

}
