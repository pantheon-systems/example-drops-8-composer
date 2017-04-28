<?php

namespace Drupal\rest\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Common base class for resource plugins.
 *
 * Note that this base class' implementation of the permissions() method
 * generates a permission for every method for a resource. If your resource
 * already has its own access control mechanism, you should opt out from this
 * default permissions() method by overriding it.
 *
 * @see \Drupal\rest\Annotation\RestResource
 * @see \Drupal\rest\Plugin\Type\ResourcePluginManager
 * @see \Drupal\rest\Plugin\ResourceInterface
 * @see plugin_api
 *
 * @ingroup third_party
 */
abstract class ResourceBase extends PluginBase implements ContainerFactoryPluginInterface, ResourceInterface {

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serializerFormats = $serializer_formats;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest')
    );
  }

  /**
   * Implements ResourceInterface::permissions().
   *
   * Every plugin operation method gets its own user permission. Example:
   * "restful delete entity:node" with the title "Access DELETE on Node
   * resource".
   */
  public function permissions() {
    $permissions = [];
    $definition = $this->getPluginDefinition();
    foreach ($this->availableMethods() as $method) {
      $lowered_method = strtolower($method);
      $permissions["restful $lowered_method $this->pluginId"] = [
        'title' => $this->t('Access @method on %label resource', ['@method' => $method, '%label' => $definition['label']]),
      ];
    }
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = new RouteCollection();

    $definition = $this->getPluginDefinition();
    $canonical_path = isset($definition['uri_paths']['canonical']) ? $definition['uri_paths']['canonical'] : '/' . strtr($this->pluginId, ':', '/') . '/{id}';
    $create_path = isset($definition['uri_paths']['https://www.drupal.org/link-relations/create']) ? $definition['uri_paths']['https://www.drupal.org/link-relations/create'] : '/' . strtr($this->pluginId, ':', '/');

    $route_name = strtr($this->pluginId, ':', '.');

    $methods = $this->availableMethods();
    foreach ($methods as $method) {
      $route = $this->getBaseRoute($canonical_path, $method);

      switch ($method) {
        case 'POST':
          $route->setPath($create_path);
          // Restrict the incoming HTTP Content-type header to the known
          // serialization formats.
          $route->addRequirements(['_content_type_format' => implode('|', $this->serializerFormats)]);
          $collection->add("$route_name.$method", $route);
          break;

        case 'PATCH':
          // Restrict the incoming HTTP Content-type header to the known
          // serialization formats.
          $route->addRequirements(['_content_type_format' => implode('|', $this->serializerFormats)]);
          $collection->add("$route_name.$method", $route);
          break;

        case 'GET':
        case 'HEAD':
          // Restrict GET and HEAD requests to the media type specified in the
          // HTTP Accept headers.
          foreach ($this->serializerFormats as $format_name) {
            // Expose one route per available format.
            $format_route = clone $route;
            $format_route->addRequirements(['_format' => $format_name]);
            $collection->add("$route_name.$method.$format_name", $format_route);
          }
          break;

        default:
          $collection->add("$route_name.$method", $route);
          break;
      }
    }

    return $collection;
  }

  /**
   * Provides predefined HTTP request methods.
   *
   * Plugins can override this method to provide additional custom request
   * methods.
   *
   * @return array
   *   The list of allowed HTTP request method strings.
   */
  protected function requestMethods() {
    return [
      'HEAD',
      'GET',
      'POST',
      'PUT',
      'DELETE',
      'TRACE',
      'OPTIONS',
      'CONNECT',
      'PATCH',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function availableMethods() {
    $methods = $this->requestMethods();
    $available = [];
    foreach ($methods as $method) {
      // Only expose methods where the HTTP request method exists on the plugin.
      if (method_exists($this, strtolower($method))) {
        $available[] = $method;
      }
    }
    return $available;
  }

  /**
   * Gets the base route for a particular method.
   *
   * @param string $canonical_path
   *   The canonical path for the resource.
   * @param string $method
   *   The HTTP method to be used for the route.
   *
   * @return \Symfony\Component\Routing\Route
   *   The created base route.
   */
  protected function getBaseRoute($canonical_path, $method) {
    return new Route($canonical_path, [
      '_controller' => 'Drupal\rest\RequestHandler::handle',
    ],
      $this->getBaseRouteRequirements($method),
      [],
      '',
      [],
      // The HTTP method is a requirement for this route.
      [$method]
    );
  }

  /**
   * Gets the base route requirements for a particular method.
   *
   * @param $method
   *   The HTTP method to be used for the route.
   *
   * @return array
   *   An array of requirements for parameters.
   */
  protected function getBaseRouteRequirements($method) {
    $lower_method = strtolower($method);
    // Every route MUST have requirements that result in the access manager
    // having access checks to check. If it does not, the route is made
    // inaccessible. So, we default to granting access to everyone. If a
    // permission exists, then we add that below. The access manager requires
    // that ALL access checks must grant access, so this still results in
    // correct behavior.
    $requirements = [
      '_access' => 'TRUE',
    ];

    // Only specify route requirements if the default permission exists. For any
    // more advanced route definition, resource plugins extending this base
    // class must override this method.
    $permission = "restful $lower_method $this->pluginId";
    if (isset($this->permissions()[$permission])) {
      $requirements['_permission'] = $permission;
    }

    return $requirements;
  }

}
