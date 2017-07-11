<?php
namespace AgenDAV\Authentication;

use AgenDAV\Controller\Authentication;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication method using HTTP basic authentication
 */
class HttpBasic implements AuthenticationMethodInterface
{
    /**
     * Try to login using the Authorization HTTP header
     * @param  Request     $request HTTP request
     * @param  Application $app     Application object
     * @return bool                 true if login is successful, false otherwise
     */
    public static function login(Request $request, Application $app)
    {
        if ($request->headers->get('authorization') != null) {
            $authController = new Authentication();
            if ($authController->processLogin(
                $request->headers->get('php-auth-user'),
                $request->headers->get('php-auth-pw'),
                $app
            )) {
                return true;
            }
        }
        return false;
    }
}
