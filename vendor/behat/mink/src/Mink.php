<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink;

/**
 * Mink sessions manager.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Mink
{
    private $defaultSessionName;

    /**
     * Sessions.
     *
     * @var Session[]
     */
    private $sessions = array();

    /**
     * Initializes manager.
     *
     * @param Session[] $sessions
     */
    public function __construct(array $sessions = array())
    {
        foreach ($sessions as $name => $session) {
            $this->registerSession($name, $session);
        }
    }

    /**
     * Stops all started sessions.
     */
    public function __destruct()
    {
        $this->stopSessions();
    }

    /**
     * Registers new session.
     *
     * @param string  $name
     * @param Session $session
     */
    public function registerSession($name, Session $session)
    {
        $name = strtolower($name);

        $this->sessions[$name] = $session;
    }

    /**
     * Checks whether session with specified name is registered.
     *
     * @param string $name
     *
     * @return Boolean
     */
    public function hasSession($name)
    {
        return isset($this->sessions[strtolower($name)]);
    }

    /**
     * Sets default session name to use.
     *
     * @param string $name name of the registered session
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultSessionName($name)
    {
        $name = strtolower($name);

        if (!isset($this->sessions[$name])) {
            throw new \InvalidArgumentException(sprintf('Session "%s" is not registered.', $name));
        }

        $this->defaultSessionName = $name;
    }

    /**
     * Returns default session name or null if none.
     *
     * @return null|string
     */
    public function getDefaultSessionName()
    {
        return $this->defaultSessionName;
    }

    /**
     * Returns registered session by it's name or active one and automatically starts it if required.
     *
     * @param string $name session name
     *
     * @return Session
     *
     * @throws \InvalidArgumentException If the named session is not registered
     */
    public function getSession($name = null)
    {
        $session = $this->locateSession($name);

        // start session if needed
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }

    /**
     * Checks whether a named session (or the default session) has already been started.
     *
     * @param string $name session name - if null then the default session will be checked
     *
     * @return bool whether the session has been started
     *
     * @throws \InvalidArgumentException If the named session is not registered
     */
    public function isSessionStarted($name = null)
    {
        $session = $this->locateSession($name);

        return $session->isStarted();
    }

    /**
     * Returns session asserter.
     *
     * @param Session|string $session session object or name
     *
     * @return WebAssert
     */
    public function assertSession($session = null)
    {
        if (!($session instanceof Session)) {
            $session = $this->getSession($session);
        }

        return new WebAssert($session);
    }

    /**
     * Resets all started sessions.
     */
    public function resetSessions()
    {
        foreach ($this->sessions as $session) {
            if ($session->isStarted()) {
                $session->reset();
            }
        }
    }

    /**
     * Restarts all started sessions.
     */
    public function restartSessions()
    {
        foreach ($this->sessions as $session) {
            if ($session->isStarted()) {
                $session->restart();
            }
        }
    }

    /**
     * Stops all started sessions.
     */
    public function stopSessions()
    {
        foreach ($this->sessions as $session) {
            if ($session->isStarted()) {
                $session->stop();
            }
        }
    }

    /**
     * Returns the named or default session without starting it.
     *
     * @param string $name session name
     *
     * @return Session
     *
     * @throws \InvalidArgumentException If the named session is not registered
     */
    protected function locateSession($name = null)
    {
        $name = strtolower($name) ?: $this->defaultSessionName;

        if (null === $name) {
            throw new \InvalidArgumentException('Specify session name to get');
        }

        if (!isset($this->sessions[$name])) {
            throw new \InvalidArgumentException(sprintf('Session "%s" is not registered.', $name));
        }

        $session = $this->sessions[$name];

        return $session;
    }
}
