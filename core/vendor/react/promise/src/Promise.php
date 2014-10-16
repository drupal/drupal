<?php

namespace React\Promise;

class Promise implements CancellablePromiseInterface
{
    private $canceller;
    private $result;

    private $handlers = [];
    private $progressHandlers = [];

    private $requiredCancelRequests = 0;
    private $cancelRequests = 0;

    public function __construct(callable $resolver, callable $canceller = null)
    {
        $this->canceller = $canceller;
        $this->call($resolver);
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if (null !== $this->result) {
            return $this->result->then($onFulfilled, $onRejected, $onProgress);
        }

        if (null === $this->canceller) {
            return new static($this->resolver($onFulfilled, $onRejected, $onProgress));
        }

        $this->requiredCancelRequests++;

        return new static($this->resolver($onFulfilled, $onRejected, $onProgress), function ($resolve, $reject, $progress) {
            if (++$this->cancelRequests < $this->requiredCancelRequests) {
                return;
            }

            $this->cancel();
        });
    }

    public function cancel()
    {
        if (null === $this->canceller || null !== $this->result) {
            return;
        }

        $this->call($this->canceller);
    }

    private function resolver(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        return function ($resolve, $reject, $progress) use ($onFulfilled, $onRejected, $onProgress) {
            if ($onProgress) {
                $progressHandler = function ($update) use ($progress, $onProgress) {
                    try {
                        $progress($onProgress($update));
                    } catch (\Exception $e) {
                        $progress($e);
                    }
                };
            } else {
                $progressHandler = $progress;
            }

            $this->handlers[] = function (PromiseInterface $promise) use ($onFulfilled, $onRejected, $resolve, $reject, $progressHandler) {
                $promise
                    ->then($onFulfilled, $onRejected)
                    ->then($resolve, $reject, $progressHandler);
            };

            $this->progressHandlers[] = $progressHandler;
        };
    }

    private function resolve($value = null)
    {
        if (null !== $this->result) {
            return;
        }

        $this->settle(resolve($value));
    }

    private function reject($reason = null)
    {
        if (null !== $this->result) {
            return;
        }

        $this->settle(reject($reason));
    }

    private function progress($update = null)
    {
        if (null !== $this->result) {
            return;
        }

        foreach ($this->progressHandlers as $handler) {
            $handler($update);
        }
    }

    private function settle(PromiseInterface $result)
    {
        foreach ($this->handlers as $handler) {
            $handler($result);
        }

        $this->progressHandlers = $this->handlers = [];

        $this->result = $result;
    }

    private function call(callable $callback)
    {
        try {
            $callback(
                function ($value = null) {
                    $this->resolve($value);
                },
                function ($reason = null) {
                    $this->reject($reason);
                },
                function ($update = null) {
                    $this->progress($update);
                }
            );
        } catch (\Exception $e) {
            $this->reject($e);
        }
    }
}
