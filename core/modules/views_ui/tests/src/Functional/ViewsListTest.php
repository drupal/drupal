<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the views list.
 *
 * @group views_ui
 */
class ViewsListTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer views.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->adminUser = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the views list does not use a pager.
   */
  public function testViewsListLimit() {
    // Check if we can access the main views admin page.
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Add view');

    // Check that there is a link to the content view without a destination
    // parameter.
    $this->drupalGet('admin/structure/views');
    $links = $this->getSession()->getPage()->findAll('xpath', "//a[contains(@href, 'admin/structure/views/view/content')]");
    $this->assertStringEndsWith('admin/structure/views/view/content', $links[0]->getAttribute('href'));
    $this->assertSession()->linkByHrefExists('admin/structure/views/view/content/delete?destination');

    // Count default views to be subtracted from the limit.
    $views = count(Views::getEnabledViews());

    // Create multiples views.
    $limit = 51;
    $values = $this->config('views.view.test_view_storage')->get();
    for ($i = 1; $i <= $limit - $views; $i++) {
      $values['id'] = 'test_view_storage_new' . $i;
      unset($values['uuid']);
      $created = View::create($values);
      $created->save();
    }
    $this->drupalGet('admin/structure/views');

    // Check that all the rows are listed.
    $this->assertCount($limit, $this->xpath('//tbody/tr[contains(@class,"views-ui-list-enabled")]'));
  }

}
