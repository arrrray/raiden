<?php

namespace App\Admin\Core\Event;

use App\Admin\Core\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Login Event.
 */
class SecurityEvent extends Event
{
    public const LOGIN = 'security.login'; // LoggedIn
    public const REGISTER = 'security.register'; // User Saved
    public const RESET_REQUEST = 'security.reset.request'; // OTP Key Created
    public const RESET_PASSWORD = 'security.reset.password'; // Password Changed

    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
