<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Tests the Entity Validation API.
 *
 * @group Entity
 */
class EntityValidationTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'text'];

  /**
   * @var string
   */
  protected $entityName;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $entityUser;

  /**
   * @var string
   */
  protected $entityFieldText;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the test field.
    module_load_install('entity_test');
    entity_test_install();

    // Install required default configuration for filter module.
    $this->installConfig(['system', 'filter']);
  }

  /**
   * Creates a test entity.
   *
   * @param string $entity_type
   *   An entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created test entity.
   */
  protected function createTestEntity($entity_type) {
    $this->entityName = $this->randomMachineName();
    $this->entityUser = $this->createUser();

    // Pass in the value of the name field when creating. With the user
    // field we test setting a field after creation.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    $entity->user_id->target_id = $this->entityUser->id();
    $entity->name->value = $this->entityName;

    // Set a value for the test field.
    if ($entity->hasField('field_test_text')) {
      $this->entityFieldText = $this->randomMachineName();
      $entity->field_test_text->value = $this->entityFieldText;
    }

    return $entity;
  }

  /**
   * Tests validating test entity types.
   */
  public function testValidation() {
    // Ensure that the constraint manager is marked as cached cleared.

    // Use the protected property on the cache_clearer first to check whether
    // the constraint manager is added there.

    // Ensure that the proxy class is initialized, which has the necessary
    // method calls attached.
    \Drupal::service('plugin.cache_clearer');
    $plugin_cache_clearer = \Drupal::service('drupal.proxy_original_service.plugin.cache_clearer');
    $get_cached_discoveries = function () {
      return $this->cachedDiscoveries;
    };
    $get_cached_discoveries = $get_cached_discoveries->bindTo($plugin_cache_clearer, $plugin_cache_clearer);
    $cached_discoveries = $get_cached_discoveries();
    $cached_discovery_classes = [];
    foreach ($cached_discoveries as $cached_discovery) {
      $cached_discovery_classes[] = get_class($cached_discovery);
    }
    $this->assertTrue(in_array('Drupal\Core\Validation\ConstraintManager', $cached_discovery_classes));

    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->checkValidation($entity_type);
    }
  }

  /**
   * Executes the validation test set for a defined entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function checkValidation($entity_type) {
    $entity = $this->createTestEntity($entity_type);
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passes.');

    // Test triggering a fail for each of the constraints specified.
    $test_entity = clone $entity;
    $test_entity->id->value = -1;
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: The integer must be larger or equal to %min.', ['%name' => 'ID', '%min' => 0]));

    $test_entity = clone $entity;
    $test_entity->uuid->value = $this->randomString(129);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', ['%name' => 'UUID', '@max' => 128]));

    $test_entity = clone $entity;
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $test_entity->{$langcode_key}->value = $this->randomString(13);
    $violations = $test_entity->validate();
    // This should fail on AllowedValues and Length constraints.
    $this->assertEqual($violations->count(), 2, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('This value is too long. It should have %limit characters or less.', ['%limit' => '12']));
    $this->assertEqual($violations[1]->getMessage(), t('The value you selected is not a valid choice.'));

    $test_entity = clone $entity;
    $test_entity->type->value = NULL;
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('This value should not be null.'));

    $test_entity = clone $entity;
    $test_entity->name->value = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', ['%name' => 'Name', '@max' => 32]));

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getRoot()->getValue(), $test_entity, 'Violation root is entity.');
    $this->assertEqual($violation->getPropertyPath(), 'name.0.value', 'Violation property path is correct.');
    $this->assertEqual($violation->getInvalidValue(), $test_entity->name->value, 'Violation contains invalid value.');

    $test_entity = clone $entity;
    $test_entity->set('user_id', 9999);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%type: %id) does not exist.', ['%type' => 'user', '%id' => 9999]));

    $test_entity = clone $entity;
    $test_entity->field_test_text->format = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('The value you selected is not a valid choice.'));

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getRoot()->getValue(), $test_entity, 'Violation root is entity.');
    $this->assertEqual($violation->getPropertyPath(), 'field_test_text.0.format', 'Violation property path is correct.');
    $this->assertEqual($violation->getInvalidValue(), $test_entity->field_test_text->format, 'Violation contains invalid value.');
  }

  /**
   * Tests composite constraints.
   */
  public function testCompositeConstraintValidation() {
    $entity = $this->createTestEntity('entity_test_composite_constraint');
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 0);

    // Trigger violation condition.
    $entity->name->value = 'test';
    $entity->type->value = 'test2';
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 1);

    // Make sure we can determine this is composite constraint.
    $constraint = $violations[0]->getConstraint();
    $this->assertTrue($constraint instanceof CompositeConstraintBase, 'Constraint is composite constraint.');
    $this->assertEqual('type', $violations[0]->getPropertyPath());

    /** @var CompositeConstraintBase $constraint */
    $this->assertEqual($constraint->coversFields(), ['name', 'type'], 'Information about covered fields can be retrieved.');
  }

}
