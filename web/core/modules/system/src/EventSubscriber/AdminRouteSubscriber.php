<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds the _admin_route option to each admin route.
 */
class AdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection->all() as $route) {
      if (strpos($route->getPath(), '/admin') === 0 && !$route->hasOption('_admin_route')) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\field_ui\Routing\RouteSubscriber or
    // \Drupal\views\EventSubscriber\RouteSubscriber to ensure we add the option
    // to their routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];

    return $events;
  }

}
