<?php

namespace Drupal\KernelTests\Core\Messenger;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Messenger
 * @coversDefaultClass \Drupal\Core\Messenger\Messenger
 */
class MessengerTest extends KernelTestBase {

  /**
   * The messenger under test.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->messenger = \Drupal::service('messenger');
  }

  /**
   * @covers ::addStatus
   * @covers ::deleteByType
   * @covers ::messagesByType
   */
  public function testRemoveSingleMessage() {

    // Set two messages.
    $this->messenger->addStatus('First message (removed).');
    $this->messenger->addStatus(t('Second message with <em>markup!</em> (not removed).'));
    $messages = $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS);
    // Remove the first.
    unset($messages[0]);

    // Re-add the second.
    foreach ($messages as $message) {
      $this->messenger->addStatus($message);
    }

    // Check we only have the second one.
    $this->assertCount(1, $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS));
    $this->assertContainsEquals('Second message with <em>markup!</em> (not removed).', $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS));

  }

  /**
   * Tests we don't add duplicates.
   *
   * @covers ::all
   * @covers ::addStatus
   * @covers ::addWarning
   * @covers ::addError
   * @covers ::deleteByType
   * @covers ::deleteAll
   */
  public function testAddNoDuplicates() {

    $this->messenger->addStatus('Non Duplicated status message');
    $this->messenger->addStatus('Non Duplicated status message');

    $this->assertCount(1, $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS));

    $this->messenger->addWarning('Non Duplicated warning message');
    $this->messenger->addWarning('Non Duplicated warning message');

    $this->assertCount(1, $this->messenger->messagesByType(MessengerInterface::TYPE_WARNING));

    $this->messenger->addError('Non Duplicated error message');
    $this->messenger->addError('Non Duplicated error message');

    $messages = $this->messenger->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertCount(1, $messages);

    // Check getting all messages.
    $messages = $this->messenger->all();
    $this->assertCount(3, $messages);
    $this->assertArrayHasKey(MessengerInterface::TYPE_STATUS, $messages);
    $this->assertArrayHasKey(MessengerInterface::TYPE_WARNING, $messages);
    $this->assertArrayHasKey(MessengerInterface::TYPE_ERROR, $messages);

    // Check deletion.
    $this->messenger->deleteAll();
    $this->assertCount(0, $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS));
    $this->assertCount(0, $this->messenger->messagesByType(MessengerInterface::TYPE_WARNING));
    $this->assertCount(0, $this->messenger->messagesByType(MessengerInterface::TYPE_ERROR));

  }

  /**
   * Tests we do add duplicates with repeat flag.
   *
   * @covers ::addStatus
   * @covers ::addWarning
   * @covers ::addError
   * @covers ::deleteByType
   */
  public function testAddWithDuplicates() {

    $this->messenger->addStatus('Duplicated status message', TRUE);
    $this->messenger->addStatus('Duplicated status message', TRUE);

    $this->assertCount(2, $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS));

    $this->messenger->addWarning('Duplicated warning message', TRUE);
    $this->messenger->addWarning('Duplicated warning message', TRUE);

    $this->assertCount(2, $this->messenger->deleteByType(MessengerInterface::TYPE_WARNING));

    $this->messenger->addError('Duplicated error message', TRUE);
    $this->messenger->addError('Duplicated error message', TRUE);

    $this->assertCount(2, $this->messenger->deleteByType(MessengerInterface::TYPE_ERROR));

  }

  /**
   * Tests adding markup.
   *
   * @covers ::addStatus
   * @covers ::deleteByType
   * @covers ::messagesByType
   */
  public function testAddMarkup() {

    // Add a Markup message.
    $this->messenger->addStatus(Markup::create('Markup with <em>markup!</em>'));
    // Test duplicate Markup messages.
    $this->messenger->addStatus(Markup::create('Markup with <em>markup!</em>'));

    $this->assertCount(1, $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS));

    // Ensure that multiple Markup messages work.
    $this->messenger->addStatus(Markup::create('Markup2 with <em>markup!</em>'));

    $this->assertCount(2, $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS));

    // Test mixing of types.
    $this->messenger->addStatus(Markup::create('Non duplicate Markup / string.'));
    $this->messenger->addStatus('Non duplicate Markup / string.');
    $this->messenger->addStatus(Markup::create('Duplicate Markup / string.'), TRUE);
    $this->messenger->addStatus('Duplicate Markup / string.', TRUE);

    $this->assertCount(3, $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS));

    $this->messenger->deleteAll();

    // Check translatable string is converted to Markup.
    $this->messenger->addStatus(new TranslatableMarkup('Translatable message'));
    $messages = $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS);

    $this->assertInstanceOf(Markup::class, $messages[0]);

  }

}
