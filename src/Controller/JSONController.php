<?php

namespace AgenDAV\Controller;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

use AgenDAV\CalDAV\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Base class for JSON-returning controllers. Slim invokes __invoke per route.
 */
abstract class JSONController
{
    /** @var \AgenDAV\CalDAV\Client */
    protected $client;

    /** @var string HTTP method (used to pick parsed body vs query params) */
    protected $method = 'POST';

    /** @var array<string, string> */
    protected $headers = [];

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->client = $container->get('caldav.client');
    }

    /**
    * Slim entry point.
    */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        if ($this->method === 'POST') {
            $raw = $request->getParsedBody() ?? [];
        } elseif ($this->method === 'GET') {
            $raw = $request->getQueryParams();
        } else {
            throw new \InvalidArgumentException('Unknown method: ' . $this->method);
        }

        $input = new ParameterBag(is_array($raw) ? $raw : []);

        if (!$this->validateInput($input)) {
            return $this->generateException(
                $response,
                $this->container->get('translator')->trans('messages.error_invalidinput')
            );
        }

        return $this->controlledExecution($input, $request, $response);
    }

    /**
    * Catches the exceptions our controllers commonly raise and converts them
    * to user-facing JSON errors.
    */
    protected function controlledExecution(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $translator = $this->container->get('translator');

        try {
            return $this->execute($input, $request, $response);
        } catch (\AgenDAV\Exception\PermissionDenied $exception) {
            return $this->generateException($response, $translator->trans('messages.error_denied'));
        } catch (\AgenDAV\Exception\NotFound $exception) {
            return $this->generateException($response, $translator->trans('messages.error_element_not_found'));
        } catch (\AgenDAV\Exception\ElementModified $exception) {
            return $this->generateException($response, $translator->trans('messages.error_element_changed'));
        } catch (\AgenDAV\Exception\ConnectionProblem $exception) {
            $this->container->get('monolog')->error(sprintf(
                'Having issues contacting the CalDAV server: %s',
                var_export($exception->getMessage(), true)
            ));
            return $this->generateError($response, $translator->trans('messages.error_network_issues'), 503);
        } catch (\AgenDAV\Exception $exception) {
            $this->container->get('monolog')->warning(sprintf(
                'Received unexpected HTTP code %d (%s) for input: %s',
                $exception->getCode(),
                $exception->getMessage(),
                var_export($input->all(), true)
            ));
            return $this->generateError(
                $response,
                $translator->trans('messages.error_unexpectedhttpcode', ['%code%' => $exception->getCode()])
            );
        } catch (\Exception $exception) {
            $this->container->get('monolog')->critical(
                sprintf(
                    'Received unexpected exception %s (%s:%d): %s',
                    get_class($exception),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getMessage()
                ),
                ['input' => $input->all()]
            );
            return $this->generateError($response, $translator->trans('messages.internal_server_error'));
        }
    }

    /**
    * @return bool true if $input passes validation
    */
    protected function validateInput(ParameterBag $input)
    {
        return true;
    }

    /**
    * Performs the action.
    */
    abstract protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface;

    protected function generateException(ResponseInterface $response, string $message, int $code = 400): ResponseInterface
    {
        return $this->jsonResponse($response, ['result' => 'EXCEPTION', 'message' => $message], $code);
    }

    protected function generateError(ResponseInterface $response, string $message, int $code = 500): ResponseInterface
    {
        return $this->jsonResponse($response, ['result' => 'ERROR', 'message' => $message], $code);
    }

    /**
    * @param array|string $message
    */
    protected function generateSuccess(ResponseInterface $response, $message = ''): ResponseInterface
    {
        return $this->jsonResponse($response, ['result' => 'SUCCESS', 'message' => $message], 200);
    }

    protected function jsonResponse(ResponseInterface $response, array $payload, int $code): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload));
        $response = $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }

    /**
    * Adds a header to be applied on the JSON response.
    */
    protected function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }
}
