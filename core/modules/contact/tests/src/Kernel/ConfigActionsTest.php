<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group contact
 */
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact', 'system', 'user'];

  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('contact');
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  public function testConfigActions(): void {
    $form = ContactForm::load('personal');
    $this->assertSame('Your message has been sent.', $form->getMessage());
    $this->assertEmpty($form->getRecipients());
    $this->assertSame('/', $form->getRedirectUrl()->toString());
    $this->assertEmpty($form->getReply());
    $this->assertSame(0, $form->getWeight());

    $this->configActionManager->applyAction(
      'entity_method:contact.form:setMessage',
      $form->getConfigDependencyName(),
      'Fly, little message!',
    );
    $this->configActionManager->applyAction(
      'entity_method:contact.form:setRecipients',
      $form->getConfigDependencyName(),
      ['ben@deep.space', 'jake@deep.space'],
    );
    $this->configActionManager->applyAction(
      'entity_method:contact.form:setRedirectPath',
      $form->getConfigDependencyName(),
      '/admin/appearance',
    );
    $this->configActionManager->applyAction(
      'entity_method:contact.form:setReply',
      $form->getConfigDependencyName(),
      "From hell's heart, I reply to thee.",
    );
    $this->configActionManager->applyAction(
      'entity_method:contact.form:setWeight',
      $form->getConfigDependencyName(),
      -10,
    );

    $form = ContactForm::load($form->id());
    $this->assertSame('Fly, little message!', $form->getMessage());
    $this->assertSame(['ben@deep.space', 'jake@deep.space'], $form->getRecipients());
    $this->assertSame('/admin/appearance', $form->getRedirectUrl()->toString());
    $this->assertSame("From hell's heart, I reply to thee.", $form->getReply());
    $this->assertSame(-10, $form->getWeight());
  }

}
