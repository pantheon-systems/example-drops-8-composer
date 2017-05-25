<?php

namespace Drupal\forum\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\taxonomy\Form\OverviewTerms;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides forum overview form for the forum vocabulary.
 */
class Overview extends OverviewTerms {

  /**
   * Entity manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\forum\Form\OverviewForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    parent::__construct($module_handler, $entity_manager);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forum_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $forum_config = $this->config('forum.settings');
    $vid = $forum_config->get('vocabulary');
    $vocabulary = $this->entityManager->getStorage('taxonomy_vocabulary')->load($vid);
    if (!$vocabulary) {
      throw new NotFoundHttpException();
    }

    // Build base taxonomy term overview.
    $form = parent::buildForm($form, $form_state, $vocabulary);

    foreach (Element::children($form['terms']) as $key) {
      if (isset($form['terms'][$key]['#term'])) {
        $term = $form['terms'][$key]['#term'];
        $form['terms'][$key]['term']['#url'] = Url::fromRoute('forum.page', ['taxonomy_term' => $term->id()]);
        unset($form['terms'][$key]['operations']['#links']['delete']);
        $route_parameters = $form['terms'][$key]['operations']['#links']['edit']['url']->getRouteParameters();
        if (!empty($term->forum_container->value)) {
          $form['terms'][$key]['operations']['#links']['edit']['title'] = $this->t('edit container');
          $form['terms'][$key]['operations']['#links']['edit']['url'] = Url::fromRoute('entity.taxonomy_term.forum_edit_container_form', $route_parameters);
        }
        else {
          $form['terms'][$key]['operations']['#links']['edit']['title'] = $this->t('edit forum');
          $form['terms'][$key]['operations']['#links']['edit']['url'] = Url::fromRoute('entity.taxonomy_term.forum_edit_form', $route_parameters);
        }
        // We don't want the redirect from the link so we can redirect the
        // delete action.
        unset($form['terms'][$key]['operations']['#links']['edit']['query']['destination']);
      }
    }

    // Remove the alphabetical reset.
    unset($form['actions']['reset_alphabetical']);

    // Use the existing taxonomy overview submit handler.
    $form['terms']['#empty'] = $this->t('No containers or forums available. <a href=":container">Add container</a> or <a href=":forum">Add forum</a>.', [
      ':container' => $this->url('forum.add_container'),
      ':forum' => $this->url('forum.add_forum')
    ]);
    return $form;
  }

}
