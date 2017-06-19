<?php
namespace AgenDAV\Authentication;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interace used to implement new authentication methods
 */
interface AuthenticationMethodInterface
{
    /**
     * Use this authentication method to login
     * @param  Request     $request HTTP request
     * @param  Application $app     Application object
     * @return bool                 true if login is successful, false otherwise
     */
    public static function login(Request $request, Application $app);
}
