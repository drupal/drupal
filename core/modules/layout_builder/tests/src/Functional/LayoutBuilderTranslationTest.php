<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationTestBase;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;

/**
 * Tests that the Layout Builder works with translated content.
 *
 * @group layout_builder
 */
class LayoutBuilderTranslationTest extends ContentTranslationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'contextual',
    'entity_test',
    'layout_builder',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->doSetup();
    $this->setUpViewDisplay();
    $this->setUpEntities();
  }

  /**
   * Tests that layout overrides work when created after a translation.
   */
  public function testTranslationBeforeLayoutOverride(): void {
    $assert_session = $this->assertSession();

    $this->addEntityTranslation();

    $entity_url = $this->entity->toUrl()->toString();
    $language = \Drupal::languageManager()->getLanguage($this->langcodes[2]);
    $translated_entity_url = $this->entity->toUrl('canonical', ['language' => $language])->toString();
    $translated_layout_url = $translated_entity_url . '/layout';

    $this->drupalGet($entity_url);
    $assert_session->pageTextNotContains('The translated field value');
    $assert_session->pageTextContains('The untranslated field value');
    $assert_session->linkExists('Layout');

    $this->drupalGet($translated_entity_url);
    $assert_session->pageTextNotContains('The untranslated field value');
    $assert_session->pageTextContains('The translated field value');
    $assert_session->linkNotExists('Layout');

    $this->drupalGet($translated_layout_url);
    $assert_session->pageTextContains('Access denied');

    $this->addLayoutOverride();

    $this->drupalGet($entity_url);
    $assert_session->pageTextNotContains('The translated field value');
    $assert_session->pageTextContains('The untranslated field value');
    $assert_session->pageTextContains('Powered by Drupal');

    // Ensure that the layout change propagates to the translated entity.
    $this->drupalGet($translated_entity_url);
    $assert_session->pageTextNotContains('The untranslated field value');
    $assert_session->pageTextContains('The translated field value');
    $assert_session->pageTextContains('Powered by Drupal');
  }

  /**
   * Tests that layout overrides work when created before a translation.
   */
  public function testLayoutOverrideBeforeTranslation(): void {
    $assert_session = $this->assertSession();

    $entity_url = $this->entity->toUrl()->toString();
    $language = \Drupal::languageManager()->getLanguage($this->langcodes[2]);

    $this->addLayoutOverride();

    $this->drupalGet($entity_url);
    $assert_session->pageTextNotContains('The translated field value');
    $assert_session->pageTextContains('The untranslated field value');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->linkExists('Layout');

    $this->addEntityTranslation();
    $translated_entity_url = $this->entity->toUrl('canonical', ['language' => $language])->toString();
    $translated_layout_url = $translated_entity_url . '/layout';

    $this->drupalGet($entity_url);
    $assert_session->pageTextNotContains('The translated field value');
    $assert_session->pageTextContains('The untranslated field value');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->linkExists('Layout');

    $this->drupalGet($translated_entity_url);
    $assert_session->pageTextNotContains('The untranslated field value');
    $assert_session->pageTextContains('The translated field value');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    $this->drupalGet($translated_layout_url);
    $assert_session->pageTextContains('Access denied');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    $permissions = parent::getTranslatorPermissions();
    $permissions[] = 'view test entity translations';
    $permissions[] = 'view test entity';
    $permissions[] = 'configure any layout';
    return $permissions;
  }

  /**
   * Setup translated entity with layouts.
   */
  protected function setUpEntities(): void {
    $this->drupalLogin($this->administrator);

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test entity.
    $id = $this->createEntity([
      $this->fieldName => [['value' => 'The untranslated field value']],
      'name' => 'Test entity',
    ], $this->langcodes[0]);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $this->entity = $storage->load($id);
  }

  /**
   * Set up the View Display.
   */
  protected function setUpViewDisplay(): void {
    EntityViewDisplay::create([
      'targetEntityType' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->setComponent($this->fieldName, ['type' => 'string'])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Adds an entity translation.
   */
  protected function addEntityTranslation(): void {
    $user = $this->loggedInUser;
    $this->drupalLogin($this->translator);
    $add_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_add", [
      $this->entityTypeId => $this->entity->id(),
      'source' => $this->langcodes[0],
      'target' => $this->langcodes[2],
    ]);
    $this->drupalGet($add_translation_url);
    $this->submitForm(["{$this->fieldName}[0][value]" => 'The translated field value'], 'Save');
    $this->drupalLogin($user);
  }

  /**
   * Adds a layout override.
   */
  protected function addLayoutOverride(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $entity_url = $this->entity->toUrl()->toString();
    $layout_url = $entity_url . '/layout';
    $this->drupalGet($layout_url);
    $assert_session->pageTextNotContains('The translated field value');
    $assert_session->pageTextContains('The untranslated field value');

    // Adjust the layout.
    $assert_session->linkExists('Add block');
    $this->clickLink('Add block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->pressButton('Add block');

    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->buttonExists('Save layout');
    $page->pressButton('Save layout');
  }

}
