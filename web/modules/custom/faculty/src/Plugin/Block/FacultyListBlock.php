<?php

namespace Drupal\faculty\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;

/**
 * Provides a 'FacultyListBlock' block.
 *
 * @Block(
 *   id = "faculty_list_block",
 *   admin_label = @Translation("Faculty list block"),
 * )
 */
class FacultyListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Vocabulary machine name for staff departments.
   *
   * @var string
   */
  const VOCABULARY_DEPARTMENTS = 'departments';

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FacultyListBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $departments = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree(self::VOCABULARY_DEPARTMENTS, 0, 1, TRUE);

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $staff_ids = $query
      ->condition('type', 'staff')
      ->condition('field_faculty', TRUE)
      ->condition('field_department', '', '<>')
      ->sort('title')
      ->execute();

    $staff = Node::loadMultiple($staff_ids);

    $items = [];
    foreach ($departments as $department) {
      $items[$department->id()] = ['department' => $department];
      foreach ($staff as $member) {
        if ($member->get('field_department')->target_id == $department->id()) {
          $items[$department->id()]['staff'][] = $member;
        }
      }
    }

    return [
      '#theme' => 'faculty_list_block',
      '#items' => $items,
      '#attached' => [
        'library' => [
          'faculty/filter',
        ],
      ],
    ];
  }

}
