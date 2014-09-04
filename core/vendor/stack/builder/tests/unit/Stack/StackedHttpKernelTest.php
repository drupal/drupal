<?php

namespace Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class StackedHttpKernelTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function handleShouldDelegateToApp()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));
        $kernel = new StackedHttpKernel($app, array($app));

        $request = Request::create('/');
        $response = $kernel->handle($request);

        $this->assertSame('ok', $response->getContent());
    }

    /** @test */
    public function handleShouldStillDelegateToAppWithMiddlewares()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));
        $foo = $this->getHttpKernelMock(new Response('foo'));
        $bar = $this->getHttpKernelMock(new Response('bar'));
        $kernel = new StackedHttpKernel($app, array($app, $foo, $bar));

        $request = Request::create('/');
        $response = $kernel->handle($request);

        $this->assertSame('ok', $response->getContent());
    }

    /** @test */
    public function terminateShouldDelegateToMiddlewares()
    {
        $app = $this->getTerminableMock(new Response('ok'));
        $foo = $this->getTerminableMock();
        $bar = $this->getTerminableMock();
        $kernel = new StackedHttpKernel($app, array($app, $foo, $bar));

        $request = Request::create('/');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    private function getHttpKernelMock(Response $response)
    {
        $app = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $app->expects($this->any())
            ->method('handle')
            ->with($this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
            ->will($this->returnValue($response));

        return $app;
    }

    private function getTerminableMock(Response $response = null)
    {
        $app = $this->getMock('Stack\TerminableHttpKernel');
        if ($response) {
            $app->expects($this->any())
                ->method('handle')
                ->with($this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
                ->will($this->returnValue($response));
        }
        $app->expects($this->once())
            ->method('terminate')
            ->with(
                $this->isInstanceOf('Symfony\Component\HttpFoundation\Request'),
                $this->isInstanceOf('Symfony\Component\HttpFoundation\Response')
            );

        return $app;
    }
}
