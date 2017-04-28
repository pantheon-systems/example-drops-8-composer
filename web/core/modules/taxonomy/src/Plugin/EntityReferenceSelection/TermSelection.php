<?php

namespace Drupal\taxonomy\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:taxonomy_term",
 *   label = @Translation("Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 1
 * )
 */
class TermSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // @todo: How to set access, as vocabulary is now config?
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['target_bundles']['#title'] = $this->t('Available Vocabularies');

    // Sorting is not possible for taxonomy terms because we use
    // \Drupal\taxonomy\TermStorageInterface::loadTree() to retrieve matches.
    $form['sort']['#access'] = FALSE;

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      $this->configuration['handler_settings']['sort'] = ['field' => 'name', 'direction' => 'asc'];
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];

    $bundles = $this->entityManager->getBundleInfo('taxonomy_term');
    $handler_settings = $this->configuration['handler_settings'];
    $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);

    foreach ($bundle_names as $bundle) {
      if ($vocabulary = Vocabulary::load($bundle)) {
        if ($terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
          foreach ($terms as $term) {
            $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityManager->getTranslationFromContext($term)->label());
          }
        }
      }
    }

    return $options;
  }

}
