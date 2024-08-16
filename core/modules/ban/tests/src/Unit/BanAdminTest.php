<?php

declare(strict_types=1);

namespace Drupal\Tests\ban\Unit;

use Drupal\ban\BanIpManagerInterface;
use Drupal\ban\Form\BanAdmin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the BanAdmin form.
 *
 * @coversDefaultClass \Drupal\ban\Form\BanAdmin
 * @group ban
 */
class BanAdminTest extends UnitTestCase {

  /**
   * Tests various user input to confirm correct validation.
   *
   * @covers ::validateForm
   * @dataProvider providerIpValidation
   */
  public function testIpValidation(string $ip, bool $isBanned, ?string $error): void {
    $manager = $this->getIpManagerMock();
    $manager->expects($this->once())
      ->method('isBanned')
      ->with($ip)
      ->willReturn($isBanned);

    $formObject = new BanAdmin($manager);
    $formObject->setStringTranslation($this->getStringTranslationStub());
    $formObject->setRequestStack($this->getRequestStackMock());

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->any())
      ->method('getValue')
      ->with('ip')
      ->willReturn($ip);

    if ($error === NULL) {
      $formState->expects($this->never())
        ->method('setErrorByName');
    }
    else {
      $formState->expects($this->once())
        ->method('setErrorByName')
        ->with('ip', $error);
    }

    $form = [];
    $formObject->validateForm($form, $formState);
  }

  /**
   * Test form submission.
   */
  public function testSubmit(): void {
    $ip = '1.2.3.4';

    $manager = $this->getIpManagerMock();
    $manager->expects($this->once())
      ->method('banIp')
      ->with($ip);

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())->method('addStatus');

    $formObject = new BanAdmin($manager);
    $formObject->setStringTranslation($this->getStringTranslationStub());
    $formObject->setMessenger($messenger);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->any())
      ->method('getValue')
      ->with('ip')
      ->willReturn($ip);

    $form = [];
    $formObject->submitForm($form, $formState);
  }

  /**
   * Test passing an IP address as a route parameter.
   *
   * @covers ::buildForm
   */
  public function testRouteParameter(): void {
    $ip = '1.2.3.4';
    $formObject = new BanAdmin($this->getIpManagerMock());
    $formObject->setStringTranslation($this->getStringTranslationStub());
    $formState = $this->createMock(FormStateInterface::class);
    $form = $formObject->buildForm([], $formState, $ip);
    $this->assertSame($ip, $form['ip']['#default_value']);
  }

  /**
   * Data provider for testIpValidation().
   */
  public static function providerIpValidation(): array {
    return [
      'valid ip' => ['1.2.3.3', FALSE, NULL],
      'already blocked' => ['1.2.3.3', TRUE, 'This IP address is already banned.'],
      'reserved ip' => ['255.255.255.255', FALSE, 'Enter a valid IP address.'],
      'fqdn' => ['test.example.com', FALSE, 'Enter a valid IP address.'],
      'empty' => ['', FALSE, 'Enter a valid IP address.'],
      'client ip' => ['127.0.0.1', FALSE, 'You may not ban your own IP address.'],
    ];
  }

  /**
   * Get a request stack with a dummy IP.
   */
  protected function getRequestStackMock(): RequestStack {
    $request = $this->createMock(Request::class);
    $request->expects($this->any())
      ->method('getClientIp')
      ->willReturn('127.0.0.1');

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    return $requestStack;
  }

  /**
   * Get the mocked IP manager service.
   */
  protected function getIpManagerMock(): BanIpManagerInterface {
    $manager = $this->createMock(BanIpManagerInterface::class);
    $manager->expects($this->any())
      ->method('findAll')
      ->willReturn([]);
    return $manager;
  }

}
