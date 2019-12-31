<?php

namespace TailgateWeb\Handlers;

use Slim\Error\AbstractErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use Throwable;

class MyHtmlErrorRenderer extends AbstractErrorRenderer
{
    private $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $title = 'Application Error';
        $message = 'A website error has occurred. Sorry for the temporary inconvenience.</p>';

        if (!$displayErrorDetails) {
            $message = '<p>The application could not run because of the following error:</p>';
            $message .= '<h2>Details</h2>';
            $message .= $this->renderExceptionFragment($exception);
        } else {

            $title = "{$exception->getCode()}";
            $message = "{$exception->getMessage()}";

            if ($exception instanceof HttpNotFoundException) {
                $title = '404 Not Found';
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $title = '405 Method Not Allowed';
            } else {
                $title = '500 Internal Server Error';
            }

        }

        return $this->view->fetch('error.twig', compact('title', 'message'));
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function renderExceptionFragment(Throwable $exception): string
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }
}
