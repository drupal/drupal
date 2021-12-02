<?php

namespace Drupal\Tests\user\Kernel\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;

/**
 * @coversDefaultClass \Drupal\user\Form\UserLoginForm
 * @group user
 */
class UserLoginFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * @group legacy
   */
  public function testConstructorDeprecations() {
    $this->expectDeprecation('Passing the flood service to Drupal\user\Form\UserLoginForm::__construct is deprecated in drupal:9.1.0 and is replaced by user.flood_control in drupal:10.0.0. See https://www.drupal.org/node/3067148');
    $flood = $this->prophesize(FloodInterface::class);
    $user_storage = $this->prophesize(UserStorageInterface::class);
    $user_auth = $this->prophesize(UserAuthInterface::class);
    $renderer = $this->prophesize(RendererInterface::class);
    $bare_html_renderer = $this->prophesize(BareHtmlPageRendererInterface::class);
    $form = new UserLoginForm(
      $flood->reveal(),
      $user_storage->reveal(),
      $user_auth->reveal(),
      $renderer->reveal(),
      $bare_html_renderer->reveal()
    );
    $this->assertNotNull($form);
  }

}
