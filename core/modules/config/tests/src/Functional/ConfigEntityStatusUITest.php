<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuration entity status UI functionality.
 *
 * @group config
 */
class ConfigEntityStatusUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests status operations.
   */
  public function testCRUD() {
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));

    $id = strtolower($this->randomMachineName());
    $edit = [
      'id' => $id,
      'label' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');

    $entity = \Drupal::entityTypeManager()->getStorage('config_test')->load($id);

    // Disable an entity.
    $disable_url = $entity->toUrl('disable');
    $this->assertLinkByHref($disable_url->toString());
    $this->drupalGet($disable_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoLinkByHref($disable_url->toString());

    // Enable an entity.
    $enable_url = $entity->toUrl('enable');
    $this->assertLinkByHref($enable_url->toString());
    $this->drupalGet($enable_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoLinkByHref($enable_url->toString());
  }

}
