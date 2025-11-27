<?php

declare(strict_types=1);

namespace Drupal\mailer_capture_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Mailer capture test form.
 */
class MailerCaptureTestForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get(MailerInterface::class));
  }

  /**
   * Constructs the mailer capture test form.
   *
   * @param \Symfony\Component\Mailer\MailerInterface $mailer
   *   The mailer service.
   */
  public function __construct(protected MailerInterface $mailer) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailer_capture_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => 'Send Mail',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $email = new Email();
    $email->subject('Test message')
      ->from('admin@localhost.localdomain')
      ->text('Hello test runner!');

    $this->mailer->send($email->to('test@localhost.localdomain'));
  }

}
