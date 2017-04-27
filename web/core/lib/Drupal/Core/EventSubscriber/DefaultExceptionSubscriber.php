<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Last-chance handler for exceptions.
 *
 * This handler will catch any exceptions not caught elsewhere and report
 * them as an error page.
 */
class DefaultExceptionSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * @var string
   *
   * One of the error level constants defined in bootstrap.inc.
   */
  protected $errorLevel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DefaultExceptionSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the configured error level.
   *
   * @return string
   */
  protected function getErrorLevel() {
    if (!isset($this->errorLevel)) {
      $this->errorLevel = $this->configFactory->get('system.logging')->get('error_level');
    }
    return $this->errorLevel;
  }

  /**
   * Handles any exception as a generic error page for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  protected function onHtml(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionally in update.php.
    $message = '';
    if (error_displayable($error)) {
      // If error type is 'User notice' then treat it as debug information
      // instead of an error message.
      // @see debug()
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
      }

      // Attempt to reduce verbosity by removing DRUPAL_ROOT from the file path
      // in the message. This does not happen for (false) security.
      $root_length = strlen(DRUPAL_ROOT);
      if (substr($error['%file'], 0, $root_length) == DRUPAL_ROOT) {
        $error['%file'] = substr($error['%file'], $root_length + 1);
      }

      unset($error['backtrace']);

      if ($this->getErrorLevel() != ERROR_REPORTING_DISPLAY_VERBOSE) {
        // Without verbose logging, use a simple message.

        // We call SafeMarkup::format directly here, rather than use t() since
        // we are in the middle of error handling, and we don't want t() to
        // cause further errors.
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file).', $error);
      }
      else {
        // With verbose logging, we will also include a backtrace.

        $backtrace_exception = $exception;
        while ($backtrace_exception->getPrevious()) {
          $backtrace_exception = $backtrace_exception->getPrevious();
        }
        $backtrace = $backtrace_exception->getTrace();
        // First trace is the error itself, already contained in the message.
        // While the second trace is the error source and also contained in the
        // message, the message doesn't contain argument values, so we output it
        // once more in the backtrace.
        array_shift($backtrace);

        // Generate a backtrace containing only scalar argument values.
        $error['@backtrace'] = Error::formatBacktrace($backtrace);
        $message = SafeMarkup::format('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $error);
      }
    }

    $content = $this->t('The website encountered an unexpected error. Please try again later.');
    $content .= $message ? '</br></br>' . $message : '';
    $response = new Response($content, 500);

    if ($exception instanceof HttpExceptionInterface) {
      $response->setStatusCode($exception->getStatusCode());
      $response->headers->add($exception->getHeaders());
    }
    else {
      $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR, '500 Service unavailable (with message)');
    }

    $event->setResponse($response);
  }

  /**
   * Handles any exception as a generic error page for JSON.
   *
   * @todo This should probably check the error reporting level.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  protected function onJson(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);

    // Display the message if the current error reporting level allows this type
    // of message to be displayed,
    $data = NULL;
    if (error_displayable($error) && $message = $exception->getMessage()) {
      $data = ['message' => sprintf('A fatal error occurred: %s', $message)];
    }

    $response = new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    if ($exception instanceof HttpExceptionInterface) {
      $response->setStatusCode($exception->getStatusCode());
      $response->headers->add($exception->getHeaders());
    }

    $event->setResponse($response);
  }

  /**
   * Handles an HttpExceptionInterface exception for unknown formats.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  protected function onFormatUnknown(GetResponseForExceptionEvent $event) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|\Exception $exception */
    $exception = $event->getException();

    $response = new Response($exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
    $event->setResponse($response);
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $format = $this->getFormat($event->getRequest());
    $exception = $event->getException();

    $method = 'on' . $format;
    if (!method_exists($this, $method)) {
      if ($exception instanceof HttpExceptionInterface) {
        $this->onFormatUnknown($event);
        $response = $event->getResponse();
        $response->headers->set('Content-Type', 'text/plain');
      }
      else {
        $this->onHtml($event);
      }
    }
    else {
      $this->$method($event);
    }
  }

  /**
   * Gets the error-relevant format from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   The format as which to treat the exception.
   */
  protected function getFormat(Request $request) {
    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    // These are all JSON errors for our purposes. Any special handling for
    // them can/should happen in earlier listeners if desired.
    if (in_array($format, ['drupal_modal', 'drupal_dialog', 'drupal_ajax'])) {
      $format = 'json';
    }

    // Make an educated guess that any Accept header type that includes "json"
    // can probably handle a generic JSON response for errors. As above, for
    // any format this doesn't catch or that wants custom handling should
    // register its own exception listener.
    foreach ($request->getAcceptableContentTypes() as $mime) {
      if (strpos($mime, 'html') === FALSE && strpos($mime, 'json') !== FALSE) {
        $format = 'json';
      }
    }

    return $format;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', -256];
    return $events;
  }

}
