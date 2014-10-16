<?php

namespace React\Promise\PromiseTest;

trait ProgressTestTrait
{
    /**
     * @return \React\Promise\PromiseAdapter\PromiseAdapterInterface
     */
    abstract public function getPromiseTestAdapter(callable $canceller = null);

    /** @test */
    public function progressShouldProgress()
    {
        $adapter = $this->getPromiseTestAdapter();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        $adapter->promise()
            ->then($this->expectCallableNever(), $this->expectCallableNever(), $mock);

        $adapter->progress($sentinel);
    }

    /** @test */
    public function progressShouldPropagateProgressToDownstreamPromises()
    {
        $adapter = $this->getPromiseTestAdapter();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnArgument(0));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        $adapter->promise()
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock2
            );

        $adapter->progress($sentinel);
    }

    /** @test */
    public function progressShouldPropagateTransformedProgressToDownstreamPromises()
    {
        $adapter = $this->getPromiseTestAdapter();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($sentinel));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        $adapter->promise()
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock2
            );

        $adapter->progress(1);
    }

    /** @test */
    public function progressShouldPropagateCaughtExceptionValueAsProgress()
    {
        $adapter = $this->getPromiseTestAdapter();

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException($exception));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $adapter->promise()
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock2
            );

        $adapter->progress(1);
    }

    /** @test */
    public function progressShouldForwardProgressEventsWhenIntermediaryCallbackTiedToAResolvedPromiseReturnsAPromise()
    {
        $adapter = $this->getPromiseTestAdapter();
        $adapter2 = $this->getPromiseTestAdapter();

        $promise2 = $adapter2->promise();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        // resolve BEFORE attaching progress handler
        $adapter->resolve();

        $adapter->promise()
            ->then(function () use ($promise2) {
                return $promise2;
            })
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            );

        $adapter2->progress($sentinel);
    }

    /** @test */
    public function progressShouldForwardProgressEventsWhenIntermediaryCallbackTiedToAnUnresolvedPromiseReturnsAPromise()
    {
        $adapter = $this->getPromiseTestAdapter();
        $adapter2 = $this->getPromiseTestAdapter();

        $promise2 = $adapter2->promise();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        $adapter->promise()
            ->then(function () use ($promise2) {
                return $promise2;
            })
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            );

        // resolve AFTER attaching progress handler
        $adapter->resolve();
        $adapter2->progress($sentinel);
    }

    /** @test */
    public function progressShouldForwardProgressWhenResolvedWithAnotherPromise()
    {
        $adapter = $this->getPromiseTestAdapter();
        $adapter2 = $this->getPromiseTestAdapter();

        $sentinel = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($sentinel));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($sentinel);

        $adapter->promise()
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $this->expectCallableNever(),
                $mock2
            );

        $adapter->resolve($adapter2->promise());
        $adapter2->progress($sentinel);
    }

    /** @test */
    public function progressShouldAllowResolveAfterProgress()
    {
        $adapter = $this->getPromiseTestAdapter();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->identicalTo(1));
        $mock
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->identicalTo(2));

        $adapter->promise()
            ->then(
                $mock,
                $this->expectCallableNever(),
                $mock
            );

        $adapter->progress(1);
        $adapter->resolve(2);
    }

    /** @test */
    public function progressShouldAllowRejectAfterProgress()
    {
        $adapter = $this->getPromiseTestAdapter();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->identicalTo(1));
        $mock
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->identicalTo(2));

        $adapter->promise()
            ->then(
                $this->expectCallableNever(),
                $mock,
                $mock
            );

        $adapter->progress(1);
        $adapter->reject(2);
    }

    /** @test */
    public function progressShouldReturnSilentlyOnProgressWhenAlreadyRejected()
    {
        $adapter = $this->getPromiseTestAdapter();

        $adapter->reject(1);

        $this->assertNull($adapter->progress());
    }
}
