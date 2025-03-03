<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Kernel;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Heading;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of editor entities.
 *
 * @group editor
 */
class EditorValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor5', 'editor', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithRequiredKeys = [
    'settings' => [
      "'toolbar' is a required key because editor is ckeditor5 (see config schema type editor.settings.ckeditor5).",
      "'plugins' is a required key because editor is ckeditor5 (see config schema type editor.settings.ckeditor5).",
    ],
    'image_upload' => "'status' is a required key.",
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $format = FilterFormat::create([
      'format' => 'test',
      'name' => 'Test',
    ]);
    $format->save();

    $this->entity = Editor::create([
      'format' => $format->id(),
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        // @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::getDefaultSettings()
        'toolbar' => [
          'items' => ['heading', 'bold', 'italic'],
        ],
        'plugins' => [
          'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
        ],
      ],
    ]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = [], ?array $additional_expected_validation_errors_when_missing = NULL): void {
    // TRICKY: Every Text Editor is associated with a Text Format. It must exist
    // to avoid triggering a validation error.
    // @see \Drupal\editor\EditorInterface::hasAssociatedFilterFormat
    FilterFormat::create([
      'format' => 'another',
      'name' => 'Another',
    ])->save();
    parent::testImmutableProperties(['format' => 'another']);
  }

  /**
   * Tests that validation fails if config dependencies are invalid.
   */
  public function testInvalidDependencies(): void {
    // Remove the config dependencies from the editor entity.
    $dependencies = $this->entity->getDependencies();
    $dependencies['config'] = [];
    $this->entity->set('dependencies', $dependencies);

    $this->assertValidationErrors(['' => 'This text editor requires a text format.']);

    // Things look sort-of like `filter.format.*` should fail validation
    // because they don't exist.
    $dependencies['config'] = [
      'filter.format',
      'filter.format.',
    ];
    $this->entity->set('dependencies', $dependencies);
    $this->assertValidationErrors([
      '' => 'This text editor requires a text format.',
      'dependencies.config.0' => "The 'filter.format' config does not exist.",
      'dependencies.config.1' => "The 'filter.format.' config does not exist.",
    ]);
  }

  /**
   * Tests validating an editor with an unknown plugin ID.
   */
  public function testInvalidPluginId(): void {
    $this->entity->setEditor('non_existent');
    $this->assertValidationErrors(['editor' => "The 'non_existent' plugin does not exist."]);
  }

  /**
   * Tests validating an editor with a non-existent `format`.
   */
  public function testInvalidFormat(): void {
    $this->entity->set('format', 'non_existent');
    $this->assertValidationErrors([
      '' => "The 'format' property cannot be changed.",
      'format' => "The 'filter.format.non_existent' config does not exist.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testLabelValidation(): void {
    // @todo Remove this override in https://www.drupal.org/i/3231354. The label of Editor entities is dynamically computed: it's retrieved from the associated FilterFormat entity. That issue will change this.
    // @see \Drupal\editor\Entity\Editor::label()
    $this->markTestSkipped();
  }

  /**
   * Test validation when dynamically changing image upload settings.
   *
   * Additional keys are required when image_upload.status is TRUE.
   */
  public function testImageUploadSettingsAreDynamicallyRequired(): void {
    // When image uploads are disabled, no other key-value pairs are needed.
    $this->entity->setImageUploadSettings(['status' => FALSE]);
    $this->assertValidationErrors([]);

    // But when they are enabled, many others are needed.
    $this->entity->setImageUploadSettings(['status' => TRUE]);
    $this->assertValidationErrors([
      'image_upload' => [
        "'scheme' is a required key because image_upload.status is 1 (see config schema type editor.image_upload_settings.1).",
        "'directory' is a required key because image_upload.status is 1 (see config schema type editor.image_upload_settings.1).",
        "'max_size' is a required key because image_upload.status is 1 (see config schema type editor.image_upload_settings.1).",
        "'max_dimensions' is a required key because image_upload.status is 1 (see config schema type editor.image_upload_settings.1).",
      ],
    ]);

    // Specify all required keys, but forget one.
    $this->entity->setImageUploadSettings([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'uploaded-images',
      'max_size' => '5 MB',
    ]);
    $this->assertValidationErrors(['image_upload' => "'max_dimensions' is a required key because image_upload.status is 1 (see config schema type editor.image_upload_settings.1)."]);

    // Specify all required keys.
    $this->entity->setImageUploadSettings([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'uploaded-images',
      'max_size' => '5 MB',
      'max_dimensions' => [
        'width' => 10000,
        'height' => 10000,
      ],
    ]);
    $this->assertValidationErrors([]);

    // Specify all required keys … but now disable image uploads again. This
    // should trigger a validation error from the ValidKeys constraint.
    $this->entity->setImageUploadSettings([
      'status' => FALSE,
      'scheme' => 'public',
      'directory' => 'uploaded-images',
      'max_size' => '5 MB',
      'max_dimensions' => [
        'width' => 10000,
        'height' => 10000,
      ],
    ]);
    $this->assertValidationErrors([
      'image_upload' => [
        "'scheme' is an unknown key because image_upload.status is 0 (see config schema type editor.image_upload_settings.*).",
        "'directory' is an unknown key because image_upload.status is 0 (see config schema type editor.image_upload_settings.*).",
        "'max_size' is an unknown key because image_upload.status is 0 (see config schema type editor.image_upload_settings.*).",
        "'max_dimensions' is an unknown key because image_upload.status is 0 (see config schema type editor.image_upload_settings.*).",
      ],
    ]);

    // Remove the values that the messages said are unknown.
    $this->entity->setImageUploadSettings(['status' => FALSE]);
    $this->assertValidationErrors([]);

    // Note how this is the same as the initial value. This proves that `status`
    // being FALSE prevents any meaningless key-value pairs to be present, and
    // `status` being TRUE requires those then meaningful pairs to be present.
  }

  /**
   * @testWith [{"scheme": "public"}, {}]
   *           [{"scheme": "private"}, {"image_upload.scheme": "The file storage you selected is not a visible, readable and writable stream wrapper. Possible choices: <em class=\"placeholder\">&quot;public&quot;</em>."}]
   *           [{"directory": null}, {}]
   *           [{"directory": ""}, {"image_upload.directory": "This value should not be blank."}]
   *           [{"directory": "inline\nimages"}, {"image_upload.directory": "The image upload directory is not allowed to span multiple lines or contain control characters."}]
   *           [{"directory": "foo\b\b\binline-images"}, {"image_upload.directory": "The image upload directory is not allowed to span multiple lines or contain control characters."}]
   *           [{"max_size": null}, {}]
   *           [{"max_size": "foo"}, {"image_upload.max_size": "This value must be a number of bytes, optionally with a unit such as \"MB\" or \"megabytes\". <em class=\"placeholder\">foo</em> does not represent a number of bytes."}]
   *           [{"max_size": ""}, {"image_upload.max_size": "This value must be a number of bytes, optionally with a unit such as \"MB\" or \"megabytes\". <em class=\"placeholder\"></em> does not represent a number of bytes."}]
   *           [{"max_size": "7 exabytes"}, {}]
   *           [{"max_dimensions": {"width": null, "height": 15}}, {}]
   *           [{"max_dimensions": {"width": null, "height": null}}, {}]
   *           [{"max_dimensions": {"width": null, "height": 0}}, {"image_upload.max_dimensions.height": "This value should be between <em class=\"placeholder\">1</em> and <em class=\"placeholder\">99999</em>."}]
   *           [{"max_dimensions": {"width": 100000, "height": 1}}, {"image_upload.max_dimensions.width": "This value should be between <em class=\"placeholder\">1</em> and <em class=\"placeholder\">99999</em>."}]
   */
  public function testImageUploadSettingsValidation(array $invalid_setting, array $expected_message): void {
    $this->entity->setImageUploadSettings($invalid_setting + [
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'uploaded-images',
      'max_size' => '5 MB',
      'max_dimensions' => [
        'width' => 10000,
        'height' => 10000,
      ],
    ]);
    $this->assertValidationErrors($expected_message);
  }

  /**
   * {@inheritdoc}
   */
  public function testRequiredPropertyValuesMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    parent::testRequiredPropertyValuesMissing([
      'dependencies' => [
        // @see ::testInvalidDependencies()
        // @see \Drupal\Core\Config\Plugin\Validation\Constraint\RequiredConfigDependenciesConstraintValidator
        '' => 'This text editor requires a text format.',
      ],
      'settings' => [
        'settings.plugins.ckeditor5_heading' => 'Configuration for the enabled plugin "<em class="placeholder">Headings</em>" (<em class="placeholder">ckeditor5_heading</em>) is missing.',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testRequiredPropertyKeysMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    parent::testRequiredPropertyKeysMissing([
      'dependencies' => [
        // @see ::testInvalidDependencies()
        // @see \Drupal\Core\Config\Plugin\Validation\Constraint\RequiredConfigDependenciesConstraintValidator
        '' => 'This text editor requires a text format.',
      ],
      'settings' => [
        'settings.plugins.ckeditor5_heading' => 'Configuration for the enabled plugin "<em class="placeholder">Headings</em>" (<em class="placeholder">ckeditor5_heading</em>) is missing.',
      ],
    ]);
  }

}
