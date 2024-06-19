<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views;

use Drupal\user\Entity\User;

/**
 * Tests views contextual links on nodes.
 *
 * @group node
 */
class NodeContextualLinksTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if the node page works if Contextual Links is disabled.
   *
   * All views have Contextual links enabled by default, even with the
   * Contextual links module disabled. This tests if no calls are done to the
   * Contextual links module by views when it is disabled.
   *
   * @see https://www.drupal.org/node/2379811
   */
  public function testPageWithDisabledContextualModule(): void {
    \Drupal::service('module_installer')->uninstall(['contextual']);
    \Drupal::service('module_installer')->install(['views_ui']);

    // Ensure that contextual links don't get called for admin users.
    $admin_user = User::load(1);
    $admin_user->setPassword('new_password');
    $admin_user->passRaw = 'new_password';
    $admin_user->save();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode(['promote' => 1]);

    $this->drupalLogin($admin_user);
    $this->drupalGet('node');
  }

}
