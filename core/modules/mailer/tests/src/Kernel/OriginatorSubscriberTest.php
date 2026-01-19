<?php

declare(strict_types=1);

namespace Drupal\Tests\mailer\Kernel;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mailer\EventSubscriber\OriginatorSubscriber;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * Tests default originator subscriber.
 */
#[CoversClass(OriginatorSubscriber::class)]
#[Group('mailer')]
#[RunTestsInSeparateProcesses]
class OriginatorSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
    'mailer',
    'system',
    'mailer_event_subscriber_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')
      ->set('langcode', 'en')
      ->set('mail', 'site-mail@example.com')
      ->set('name', 'Example Site')
      ->save();
  }

  /**
   * Ensure that the from address is set to the site mail address.
   */
  public function testDefaultFrom(): void {
    $expectedAddress = Address::create('Example Site <site-mail@example.com>');

    $email = (new Email())
      ->subject('Way house answer start behind old')
      ->text('We name know environmental along agree let. Traditional interest this clearly concern discover. Foot carry member your.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualFrom = $originalEmail->getFrom();
    $this->assertEquals([$expectedAddress], $actualFrom);
  }

  /**
   * Ensure that the from address is set with the correct locale.
   */
  public function testLocalizedDefaultFrom(): void {
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Update fr system.site config with custom translation.
    $languageManager = $this->container->get(LanguageManagerInterface::class);
    assert($languageManager instanceof ConfigurableLanguageManagerInterface);
    // Use a name that could trigger HTML entity replacements.
    // cspell:ignore L'Equipe de l'Agriculture
    $languageManager->getLanguageConfigOverride('fr', 'system.site')
      ->set('mail', 'site-mail-fr@example.com')
      ->set('name', "L'Equipe de l'Agriculture")
      ->save();

    $expectedAddress = new Address('site-mail-fr@example.com', "L'Equipe de l'Agriculture");

    $email = (new Email())
      ->subject('Way house answer start behind old')
      ->text('We name know environmental along agree let. Traditional interest this clearly concern discover. Foot carry member your.');

    $email->getHeaders()->add(new UnstructuredHeader('Content-Language', 'fr'));

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualFrom = $originalEmail->getFrom();
    $this->assertEquals([$expectedAddress], $actualFrom);
  }

  /**
   * Ensure that the from address can be customized.
   */
  public function testCustomFrom(): void {
    $expectedAddress = Address::create('custom-from@example.com');

    $email = (new Email())
      ->from('custom-from@example.com')
      ->subject('Notice soon as brother')
      ->text('House answer start behind. Around medical also its attorney before interesting step. Water piece on artist.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualFrom = $originalEmail->getFrom();
    $this->assertEquals([$expectedAddress], $actualFrom);
  }

  /**
   * Ensure that the from address can be customized.
   */
  public function testCustomFromSubscriber(): void {
    $expectedAddress = Address::create('subscriber-from@example.com');

    $state = $this->container->get('state');
    assert($state instanceof StateInterface);
    $state->set('mailer_event_subscriber_test.set_custom_from', $expectedAddress);

    $email = (new Email())
      ->subject('Serious inside else memory if six')
      ->text('Name have page personal assume actually study else. Court response must near however.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualFrom = $originalEmail->getFrom();
    $this->assertEquals([$expectedAddress], $actualFrom);
  }

  /**
   * Ensure that there is no message sender with default from address.
   */
  public function testDefaultMessageSender(): void {
    $email = (new Email())
      ->subject('State machine energy a production like service.')
      ->text('While call relate be easy yourself. Husband air maintain hospital of.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $this->assertNull($originalEmail->getSender());
  }

  /**
   * Ensure that the message sender is set to the site mail with custom from.
   */
  public function testDefaultMessageSenderWithCustomFrom(): void {
    $expectedAddress = Address::create('Example Site <site-mail@example.com>');

    $email = (new Email())
      ->from('custom-from@example.com')
      ->subject('Have heart cover analysis carry.')
      ->text('Billion how choice at husband. Song share develop Mr everybody.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualSender = $originalEmail->getSender();
    $this->assertEquals($expectedAddress, $actualSender);
  }

  /**
   * Ensure that the message sender is set to the site mail with custom from.
   */
  public function testCustomMessageSender(): void {
    $expectedAddress = Address::create('custom-message-sender@example.com');

    $email = (new Email())
      ->from('custom-from@example.com')
      ->sender('custom-message-sender@example.com')
      ->subject('Field return long bed after.')
      ->text('Machine energy a production. Whole same floor against major cup their. Much behind nor record rock production particular.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualSender = $originalEmail->getSender();
    $this->assertEquals($expectedAddress, $actualSender);
  }

  /**
   * Ensure that the message sender can be customized using an event subscriber.
   */
  public function testCustomMessageSenderSubscriber(): void {
    $expectedAddress = Address::create('subscriber-message-sender@example.com');

    $state = $this->container->get('state');
    assert($state instanceof StateInterface);
    $state->set('mailer_event_subscriber_test.set_custom_message_sender', $expectedAddress);

    $email = (new Email())
      ->subject('Have heart cover analysis carry.')
      ->text('Entire soon option bill fish against power.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $originalEmail = $sentMessage->getOriginalMessage();
    assert($originalEmail instanceof Email);
    $actualSender = $originalEmail->getSender();
    $this->assertEquals($expectedAddress, $actualSender);
  }

  /**
   * Ensure that the envelope sender is set to the site mail address.
   */
  public function testDefaultEnvelopeSender(): void {
    $expectedAddress = Address::create('Example Site <site-mail@example.com>');

    $email = (new Email())
      ->subject('Score somebody wall science two.')
      ->text('Style simply eat. Too both light. Herself bill economic room impact.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

  /**
   * Ensure that the envelope sender is set to the site mail with custom from.
   */
  public function testDefaultEnvelopeSenderWithCustomFrom(): void {
    $expectedAddress = Address::create('Example Site <site-mail@example.com>');

    $email = (new Email())
      ->from('custom-from@example.com')
      ->subject('Media under opportunity similar.')
      ->text('Health catch term according me together ball never. Record rock college watch week institution collection anything.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

  /**
   * Ensure that the envelope sender can be customized using a custom envelope.
   */
  public function testCustomEnvelope(): void {
    $expectedAddress = Address::create('custom-envelope-sender@example.com');

    $email = (new Email())
      ->subject('Song cover finally phone rule.')
      ->text('Billion how choice at husband. Song share develop Mr everybody. Energy wall agent political.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $customEnvelope = Envelope::create($email);
    $customEnvelope->setSender($expectedAddress);
    $sentMessage = $transport->send($email->to('foobar@example.com'), $customEnvelope);
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

  /**
   * Ensure that the envelope sender can be customized using a custom envelope.
   */
  public function testCustomEnvelopeWithCustomMessageSender(): void {
    $expectedAddress = Address::create('custom-envelope-sender@example.com');

    $email = (new Email())
      ->from('custom-from@example.com')
      ->sender('custom-message-sender@example.com')
      ->subject('First policy daughter need kind miss.')
      ->text('American whole magazine truth stop whose. On traditional measure example sense peace. Would mouth relate own chair.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $customEnvelope = Envelope::create($email);
    $customEnvelope->setSender(Address::create($expectedAddress));
    $sentMessage = $transport->send($email->to('foobar@example.com'), $customEnvelope);
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

  /**
   * Ensure that the envelope sender can be customized using an event subscriber.
   */
  public function testCustomEnvelopeSubscriber(): void {
    $expectedAddress = Address::create('subscriber-envelope-sender@example.com');

    $state = $this->container->get('state');
    assert($state instanceof StateInterface);
    $state->set('mailer_event_subscriber_test.set_custom_envelope_sender', $expectedAddress);

    $email = (new Email())
      ->subject('Court response must near however.')
      ->text('Name have page personal assume actually study else. Play test model scientist provide. City whatever amount sister.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

  /**
   * Ensure that the envelope sender can be customized using an event subscriber.
   */
  public function testCustomEnvelopeSubscriberWithCustomMessageSender(): void {
    $expectedAddress = Address::create('subscriber-envelope-sender@example.com');

    $state = $this->container->get('state');
    assert($state instanceof StateInterface);
    $state->set('mailer_event_subscriber_test.set_custom_envelope_sender', $expectedAddress);

    $email = (new Email())
      ->from('custom-from@example.com')
      ->sender('custom-message-sender@example.com')
      ->subject('Record rock college')
      ->text('Magazine service red minute. Top here box election yard as per. Blue around doctor beat tough might.');

    $transport = $this->container->get(TransportInterface::class);
    assert($transport instanceof TransportInterface);

    $sentMessage = $transport->send($email->to('foobar@example.com'));
    assert($sentMessage instanceof SentMessage);

    $envelope = $sentMessage->getEnvelope();
    $actualAddress = $envelope->getSender();
    $this->assertEquals($expectedAddress, $actualAddress);
  }

}
