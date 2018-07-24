<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceInterface;

/**
 * Utility methods for use in BrowserTestBase tests.
 *
 * This trait will not work if not used in a child of BrowserTestBase.
 */
trait WorkspaceTestUtilities {

  use BlockCreationTrait;

  /**
   * Loads a single entity by its label.
   *
   * The UI approach to creating an entity doesn't make it easy to know what
   * the ID is, so this lets us make paths for an entity after it's created.
   *
   * @param string $type
   *   The type of entity to load.
   * @param string $label
   *   The label of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function getOneEntityByLabel($type, $label) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $property = $entity_type_manager->getDefinition($type)->getKey('label');
    $entity_list = $entity_type_manager->getStorage($type)->loadByProperties([$property => $label]);
    $entity = current($entity_list);
    if (!$entity) {
      $this->fail("No {$type} entity named {$label} found.");
    }

    return $entity;
  }

  /**
   * Creates a new Workspace through the UI.
   *
   * @param string $label
   *   The label of the workspace to create.
   * @param string $id
   *   The ID of the workspace to create.
   *
   * @return \Drupal\workspaces\WorkspaceInterface
   *   The workspace that was just created.
   */
  protected function createWorkspaceThroughUi($label, $id) {
    $this->drupalPostForm('/admin/config/workflow/workspaces/add', [
      'id' => $id,
      'label' => $label,
    ], 'Save');

    $this->getSession()->getPage()->hasContent("$label ($id)");

    return Workspace::load($id);
  }

  /**
   * Adds the workspace switcher block to the site.
   *
   * This is necessary for switchToWorkspace() to function correctly.
   */
  protected function setupWorkspaceSwitcherBlock() {
    // Add the block to the sidebar.
    $this->placeBlock('workspace_switcher', [
      'id' => 'workspaceswitcher',
      'region' => 'sidebar_first',
      'label' => 'Workspace switcher',
    ]);

    // Confirm the block shows on the front page.
    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasContent('Workspace switcher'));
  }

  /**
   * Sets a given workspace as "active" for subsequent requests.
   *
   * This assumes that the switcher block has already been setup by calling
   * setupWorkspaceSwitcherBlock().
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace to set active.
   */
  protected function switchToWorkspace(WorkspaceInterface $workspace) {
    /** @var \Drupal\Tests\WebAssert $session */
    $session = $this->assertSession();
    $session->buttonExists('Activate');
    $this->drupalPostForm(NULL, ['workspace_id' => $workspace->id()], 'Activate');
    $session->pageTextContains($workspace->label() . ' is now the active workspace.');
  }

  /**
   * Creates a node by "clicking" buttons.
   *
   * @param string $label
   *   The label of the Node to create.
   * @param string $bundle
   *   The bundle of the Node to create.
   * @param bool $publish
   *   The publishing status to set.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node that was just created.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function createNodeThroughUi($label, $bundle, $publish = TRUE) {
    $this->drupalGet('/node/add/' . $bundle);

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $session->getPage();
    $page->fillField('Title', $label);
    if ($publish) {
      $page->findButton('Save')->click();
    }
    else {
      $page->uncheckField('Published');
      $page->findButton('Save')->click();
    }

    $session->getPage()->hasContent("{$label} has been created");

    return $this->getOneEntityByLabel('node', $label);
  }

  /**
   * Determine if the content list has an entity's label.
   *
   * This assertion can be used to validate a particular entity exists in the
   * current workspace.
   */
  protected function isLabelInContentOverview($label) {
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertSession()->statusCodeEquals(200);
    $page = $session->getPage();
    return $page->hasContent($label);
  }

}
