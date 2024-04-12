<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the views integration for workspaces.
 *
 * @group views
 * @group workspaces
 */
class WorkspaceViewsIntegrationTest extends ViewsKernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceFieldCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'entity_test',
    'field',
    'filter',
    'node',
    'language',
    'text',
    'views_ui',
    'workspaces',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creation timestamp that should be incremented for each new entity.
   */
  protected int $createdTimestamp = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    $this->installConfig(['filter', 'node', 'system', 'language', 'content_translation']);

    $this->installSchema('node', ['node_access']);
    $this->installSchema('workspaces', ['workspace_association']);

    $language = ConfigurableLanguage::createFromLangcode('ro');
    $language->save();

    $this->createContentType(['type' => 'page']);
    $this->container->get('content_translation.manager')->setEnabled('node', 'page', TRUE);

    // Create an entity reference field, in order to test relationship queries.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'field_name' => 'field_reference',
      'settings' => [
        'target_type' => 'entity_test_mulrevpub',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_reference',
    ])->save();
  }

  /**
   * Tests workspace query alter for views.
   *
   * @covers \Drupal\workspaces\ViewsQueryAlter::alterQueryForEntityType
   * @covers \Drupal\workspaces\ViewsQueryAlter::getRevisionTableJoin
   */
  public function testViewsQueryAlter(): void {
    // Create a test entity and two nodes.
    $test_entity = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrevpub')
      ->create(['name' => 'test entity - live']);
    $test_entity->save();
    $node_1 = $this->createNode([
      'title' => 'node - live - 1',
      'body' => 'node 1',
      'created' => $this->createdTimestamp++,
      'field_reference' => $test_entity->id(),
    ]);
    $node_2 = $this->createNode([
      'title' => 'node - live - 2',
      'body' => 'node 2',
      'created' => $this->createdTimestamp++,
    ]);

    // Create a new workspace and activate it.
    Workspace::create(['id' => 'stage', 'label' => 'Stage'])->save();
    $this->switchToWorkspace('stage');

    $view = Views::getView('frontpage');

    // Add a filter on a field that is stored in a dedicated table in order to
    // test field joins with extra conditions (e.g. 'deleted' and 'langcode').
    $view->setDisplay('page_1');
    $filters = $view->displayHandlers->get('page_1')->getOption('filters');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters + [
      'body_value' => [
        'id' => 'body_value',
        'table' => 'node__body',
        'field' => 'body_value',
        'operator' => 'not empty',
        'plugin_id' => 'string',
      ],
    ]);
    $view->execute();
    $expected = [
      ['nid' => $node_2->id()],
      ['nid' => $node_1->id()],
    ];
    $this->assertIdenticalResultset($view, $expected, ['nid' => 'nid']);

    // Add a filter on a field from a relationship, in order to test field
    // joins with extra conditions (e.g. 'deleted' and 'langcode').
    $view->destroy();
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('relationships', [
      'field_reference' => [
        'id' => 'field_reference',
        'table' => 'node__field_reference',
        'field' => 'field_reference',
        'required' => FALSE,
      ],
    ]);
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters + [
      'name' => [
        'id' => 'name',
        'table' => 'entity_test_mulrevpub_property_data',
        'field' => 'name',
        'operator' => 'not empty',
        'relationship' => 'field_reference',
      ],
    ]);
    $view->execute();

    $expected = [
      ['nid' => $node_1->id()],
    ];
    $this->assertIdenticalResultset($view, $expected, ['nid' => 'nid']);
  }

  /**
   * Tests creating a view of workspace entities.
   *
   * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  public function testCreateWorkspaceView(): void {
    $wizard = \Drupal::service('plugin.manager.views.wizard')->createInstance('standard:workspace', []);
    $form = [];
    $form_state = new FormState();
    $form = $wizard->buildForm($form, $form_state);
    $random_id = $this->randomMachineName();
    $random_label = $this->randomMachineName();

    $form_state->setValues([
      'id' => $random_id,
      'label' => $random_label,
      'base_table' => 'workspace',
    ]);

    $wizard->validateView($form, $form_state);
    $view = $wizard->createView($form, $form_state);
    $this->assertInstanceOf(ViewUI::class, $view);
    $this->assertEquals($random_id, $view->get('id'));
    $this->assertEquals($random_label, $view->get('label'));
    $this->assertEquals('workspace', $view->get('base_table'));
  }

}
