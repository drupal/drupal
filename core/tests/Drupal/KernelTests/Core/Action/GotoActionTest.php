<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Action;

use Drupal\Core\Action\Plugin\Action\GotoAction;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Action;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Goto Action.
 */
#[CoversClass(GotoAction::class)]
#[Group('Action')]
#[RunTestsInSeparateProcesses]
class GotoActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests goto action.
   */
  #[DataProvider('providerGotoAction')]
  public function testGotoAction(string $url, string $expected): void {
    $entity = $this->createMock(EntityInterface::class);
    $action = Action::create([
      'id' => 'goto',
      'plugin' => 'action_goto_action',
      'configuration' => ['url' => $url],
    ]);
    $action->save();
    $action->execute([$entity]);

    // Simulate a request to trigger the redirect.
    $request = Request::create('');
    $response = $this->container->get('http_kernel')->handle($request);
    $location = $response->headers->get('Location');

    // Convert to relative path.
    $base = rtrim(Url::fromRoute('<front>')->setAbsolute()->toString(), '/');
    if (str_starts_with($location, $base)) {
      $location = substr($location, strlen($base));
    }

    $this->assertSame(302, $response->getStatusCode());
    $this->assertSame($expected, $location);
  }

  /**
   * Data provider for ::testGotoAction().
   */
  public static function providerGotoAction(): array {
    return [
      '<front>' => ['<front>', '/'],
      'empty string' => ['', '/'],
      'internal path' => ['/user', '/user'],
      'internal path with query and fragment' => ['/user?foo=bar#baz', '/user?foo=bar#baz'],
      'external URL' => ['https://example.com', 'https://example.com'],
    ];
  }

}
