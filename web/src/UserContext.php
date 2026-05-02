<?php

namespace AgenDAV;

use AgenDAV\Data\Preferences;

/**
 * Per-request user state populated by AuthMiddleware. Lives as a singleton in
 * the DI container; PHP-FPM gives each request a fresh process so this is
 * effectively per-request.
 */
class UserContext
{
    private ?Preferences $preferences = null;
    private ?string $timezone = null;

    public function setPreferences(Preferences $preferences): void
    {
        $this->preferences = $preferences;
    }

    public function getPreferences(): ?Preferences
    {
        return $this->preferences;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
