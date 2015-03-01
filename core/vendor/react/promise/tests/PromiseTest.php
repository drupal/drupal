<?php

namespace React\Promise;

use React\Promise\PromiseAdapter\CallbackPromiseAdapter;

class PromiseTest extends TestCase
{
    use PromiseTest\FullTestTrait;

    public function getPromiseTestAdapter(callable $canceller = null)
    {
        $resolveCallback = $rejectCallback = $progressCallback = null;

        $promise = new Promise(function ($resolve, $reject, $progress) use (&$resolveCallback, &$rejectCallback, &$progressCallback) {
            $resolveCallback  = $resolve;
            $rejectCallback   = $reject;
            $progressCallback = $progress;
        }, $canceller);

        return new CallbackPromiseAdapter([
            'promise' => function () use ($promise) {
                return $promise;
            },
            'resolve' => $resolveCallback,
            'reject'  => $rejectCallback,
            'notify'  => $progressCallback,
            'settle'  => $resolveCallback,
        ]);
    }

    /** @test */
    public function shouldRejectIfResolverThrowsException()
    {
        $exception = new \Exception('foo');

        $promise = new Promise(function () use ($exception) {
            throw $exception;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);
    }

    /** @test */
    public function shouldFulfillIfFullfilledWithSimplePromise()
    {
        $adapter = $this->getPromiseTestAdapter();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo('foo'));

        $adapter->promise()
            ->then($mock);

        $adapter->resolve(new SimpleFulfilledTestPromise());
    }

    /** @test */
    public function shouldRejectIfRejectedWithSimplePromise()
    {
        $adapter = $this->getPromiseTestAdapter();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo('foo'));

        $adapter->promise()
            ->then($this->expectCallableNever(), $mock);

        $adapter->resolve(new SimpleRejectedTestPromise());
    }
}

class SimpleFulfilledTestPromise implements PromiseInterface
{
    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        try {
            if ($onFulfilled) {
                $onFulfilled('foo');
            }

            return new self('foo');
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }
}

class SimpleRejectedTestPromise implements PromiseInterface
{
    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        try {
            if ($onRejected) {
                $onRejected('foo');
            }

            return new self('foo');
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }
}
