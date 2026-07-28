<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\link\LinkTitleVisibility;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests link field widgets.
 */
#[Group('link')]
#[RunTestsInSeparateProcesses]
class LinkFieldWidgetTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'link',
    'system',
    'user',
  ];

  /**
   * Admin-defined help text used when configuring instances of Link fields.
   */
  protected string $fieldDescription = 'This is the link field description';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * Tests '#link_type' property exists on 'link_default' widget.
   *
   * Make sure the 'link_default' widget exposes a '#link_type' property on
   * its element. Modules can use it to understand if a text form element is
   * a link and also which LinkItemInterface::LINK_* is (EXTERNAL, GENERIC,
   * INTERNAL).
   */
  public function testLinkTypeOnLinkWidget(): void {
    $link_type = LinkItemInterface::LINK_EXTERNAL;
    $field_name = $this->entitySetUp($link_type, LinkTitleVisibility::Optional);
    $form = $this->container->get('entity.form_builder')->getForm(EntityTest::create());
    $this->assertEquals($link_type, $form[$field_name]['widget'][0]['uri']['#link_type']);
  }

  /**
   * Test link widget exception handled if link uri value is invalid.
   */
  public function testLinkWidgetCaughtExceptionEditingInvalidUrl(): void {
    $field_name = $this->entitySetUp(LinkItemInterface::LINK_GENERIC, LinkTitleVisibility::Optional);

    // Entities can be saved without validation, for example via migration.
    // Link fields may contain invalid uris such as external URLs without
    // scheme.
    $invalidUri = 'www.example.com';
    $invalidLinkUrlEntity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create([
        'name' => 'Test entity with invalid link URL',
        $field_name => ['uri' => $invalidUri],
      ]);
    $invalidLinkUrlEntity->save();

    // If a user without 'link to any page' permission edits an entity, widget
    // checks access by converting uri to Url object, which will throw an
    // InvalidArgumentException if uri is invalid.
    $this->setCurrentUser($this->createUser([
      'view test entity',
      'administer entity_test content',
    ]));
    $this->drupalGet("/entity_test/manage/{$invalidLinkUrlEntity->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals("{$field_name}[0][uri]", $invalidUri);
  }

  /**
   * Verifies that the widget elements have the expected descriptions.
   */
  public function testFieldDescription(): void {
    $formBuilder = $this->container->get('entity.form_builder');
    $linkTypes = [
      LinkItemInterface::LINK_INTERNAL => 'This must be an internal path such as /node/add. You can also start typing the title of a piece of content to select it. Enter &lt;front&gt; to link to the front page. Enter &lt;nolink&gt; to display link text only. Enter &lt;button&gt; to display keyboard-accessible link text only.',
      LinkItemInterface::LINK_EXTERNAL => 'This must be an external URL such as https://example.com.',
      LinkItemInterface::LINK_GENERIC => 'Start typing the title of a piece of content to select it. You can also enter an internal path such as /node/add or an external URL such as https://example.com. Enter &lt;front&gt; to link to the front page. Enter &lt;nolink&gt; to display link text only. Enter &lt;button&gt; to display keyboard-accessible link text only.',
    ];

    foreach ($linkTypes as $linkType => $typeDescription) {
      // Create two fields: one with a title subfield and one without. There are
      // different description visibility rules for these cases.
      $fieldWithTitle = $this->entitySetUp($linkType, LinkTitleVisibility::Optional);
      $fieldWithoutTitle = $this->entitySetUp($linkType, LinkTitleVisibility::Disabled);

      $form = $formBuilder->getForm(EntityTest::create());
      // Instances with a title subfield should have the type description
      // describing the uri subfield. The field help text should describe the
      // fieldset.
      $this->assertEquals($typeDescription, strip_tags((string) $form[$fieldWithTitle]['widget'][0]['uri']['#description']));
      $this->assertEquals($this->fieldDescription, $form[$fieldWithTitle]['widget']['#description']);
      // Instances without a title subfield should have both descriptions
      // describing the uri subfield in an item_list
      $this->assertEquals('item_list', $form[$fieldWithoutTitle]['widget'][0]['uri']['#description']['#theme']);
      $expectedDescriptions = [
        $this->fieldDescription,
        $typeDescription,
      ];
      $this->assertEquals($expectedDescriptions, $form[$fieldWithoutTitle]['widget'][0]['uri']['#description']['#items']);
    }
  }

  /**
   * Verify that default values are applied to the uri subfield.
   */
  public function testDefaultValues(): void {
    $formBuilder = $this->container->get('entity.form_builder');
    $linkTypes = [
      LinkItemInterface::LINK_INTERNAL => 'internal:/node/add',
      LinkItemInterface::LINK_EXTERNAL => 'https://example.com',
      LinkItemInterface::LINK_GENERIC => '',
    ];

    foreach ($linkTypes as $linkType => $defaultValue) {
      $field = $this->entitySetUp($linkType, LinkTitleVisibility::Disabled, 1, $defaultValue);

      $form = $formBuilder->getForm(EntityTest::create());
      // Normalize internal URIs.
      if ($linkType === LinkItemInterface::LINK_INTERNAL) {
        $defaultValue = substr($defaultValue, 9);
      }
      $this->assertEquals($defaultValue, $form[$field]['widget'][0]['uri']['#value']);
    }
  }

  /**
   * Initializes a link field on the entity_test entity type.
   *
   * @param int $linkType
   *   One of the LinkItemInterface type constants.
   * @param \Drupal\link\LinkTitleVisibility $titleVisibilitySetting
   *   The title subfield's visibility.
   * @param int $cardinality
   *   (optional) The field's cardinality, defaults to 1.
   * @param string|null $defaultValue
   *   (optional) A default value for the uri subfield, defaults to null.
   *
   * @return string
   *   The generated field name.
   */
  protected function entitySetUp(int $linkType, LinkTitleVisibility $titleVisibilitySetting, int $cardinality = 1, ?string $defaultValue = NULL): string {
    $fieldName = $this->randomMachineName();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => $cardinality,
    ]);
    $fieldStorage->save();
    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'label' => 'Link',
      'bundle' => 'entity_test',
      'settings' => [
        'title' => $titleVisibilitySetting->value,
        'link_type' => $linkType,
      ],
      'description' => $this->fieldDescription,
    ]);
    if ($defaultValue !== NULL) {
      $fieldConfig->setDefaultValue(['uri' => $defaultValue]);
    }
    $fieldConfig->save();

    $this->container->get('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($fieldName, ['type' => 'link_default'])
      ->save();

    return $fieldName;
  }

}
