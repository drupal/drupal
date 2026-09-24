<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Default Admin theme for nodes.
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class AdminNodeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'default_admin_form_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'default_admin';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('admin', 'default_admin')
      ->save();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'create page content',
      'edit any page content',
      'administer nodes',
    ]));
  }

  /**
   * Tests the published checkbox on an entity form that sets #tree.
   *
   * The theme copies the footer into its sidebar, and a group is identified by
   * its #parents, so without pinning them the copy registers under a different
   * name on a treed form. The checkbox would then render nowhere while still
   * being processed on submit, which unpublishes the entity on save.
   */
  public function testPublishedCheckboxOnTreedForm(): void {
    $node = $this->drupalCreateNode(['type' => 'page', 'status' => TRUE]);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('status[value]');
    $this->assertSession()->checkboxChecked('status[value]');

    $this->submitForm([], 'Save');

    $node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadUnchanged($node->id());
    $this->assertTrue($node->isPublished());
  }

}
