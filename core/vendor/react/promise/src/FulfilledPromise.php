<?php

namespace React\Promise;

class FulfilledPromise implements CancellablePromiseInterface
{
    private $value;

    public function __construct($value = null)
    {
        if ($value instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\FulfilledPromise with a promise. Use React\Promise\resolve($promiseOrValue) instead.');
        }

        $this->value = $value;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        try {
            $value = $this->value;

            if (null !== $onFulfilled) {
                $value = $onFulfilled($value);
            }

            return resolve($value);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function cancel()
    {
    }
}
