<?php

namespace AgenDAV;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AutologinState
{
    public function __construct(
        private SessionInterface $session,
        private bool $enabled,
        private string $username,
        private bool $hidePreferences,
        private bool $readOnly
    ) {
    }

    public function isDashboardSession(): bool
    {
        if ($this->session->has('autologin') && $this->session->get('autologin') === true) {
            return true;
        }

        return $this->enabled
            && $this->username !== ''
            && $this->session->has('username')
            && $this->session->get('username') === $this->username;
    }

    public function shouldHidePreferences(): bool
    {
        return $this->hidePreferences && $this->isDashboardSession();
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly && $this->isDashboardSession();
    }
}
