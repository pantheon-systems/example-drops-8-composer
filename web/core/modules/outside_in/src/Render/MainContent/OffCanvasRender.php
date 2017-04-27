<?php

namespace Drupal\outside_in\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\MainContent\DialogRenderer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\outside_in\Ajax\OpenOffCanvasDialogCommand;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for offcanvas dialog requests.
 */
class OffCanvasRender extends DialogRenderer {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OffCanvasRender.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(TitleResolverInterface $title_resolver, RendererInterface $renderer) {
    parent::__construct($title_resolver);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    // First render the main content, because it might provide a title.
    $content = $this->renderer->renderRoot($main_content);

    // Attach the library necessary for using the OpenOffCanvasDialogCommand and
    // set the attachments for this Ajax response.
    $main_content['#attached']['library'][] = 'outside_in/drupal.off_canvas';
    $response->setAttachments($main_content['#attached']);

    // If the main content doesn't provide a title, use the title resolver.
    $title = isset($main_content['#title']) ? $main_content['#title'] : $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $options = $request->request->get('dialogOptions', []);

    $response->addCommand(new OpenOffCanvasDialogCommand($title, $content, $options));
    return $response;
  }

}
