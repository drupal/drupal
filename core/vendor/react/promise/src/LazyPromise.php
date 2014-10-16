<?php

namespace React\Promise;

class LazyPromise implements CancellablePromiseInterface
{
    private $factory;
    private $promise;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        return $this->promise()->then($onFulfilled, $onRejected, $onProgress);
    }

    public function cancel()
    {
        return $this->promise()->cancel();
    }

    private function promise()
    {
        if (null === $this->promise) {
            try {
                $this->promise = resolve(call_user_func($this->factory));
            } catch (\Exception $exception) {
                $this->promise = new RejectedPromise($exception);
            }
        }

        return $this->promise;
    }
}
