<?php

namespace Drupal\event_archive\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\event_list\EventListManager;

/**
 * Provides a 'EventArchiveBlock' block.
 *
 * @Block(
 *   id = "event_archive_block",
 *   admin_label = @Translation("Event archive block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class EventArchiveBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\event_list\EventListManager definition.
   *
   * @var \Drupal\event_list\EventListManager
   */
  protected $eventListManager;

  /**
   * Constructs a new EventArchiveBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\event_list\EventListManager $event_list_manager
   *   The event list manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EventListManager $event_list_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventListManager = $event_list_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_list.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $event_data = $this->eventListManager->getArchivedEventItems($node->id());
    $pager = pager_default_initialize($event_data['total'], EventListManager::PAGE_SIZE);

    return [
      '#theme' => 'event_archive_block',
      '#items' => $event_data['items'],
      '#years' => $event_data['years'],
      '#months' => $event_data['months'],
      '#current_year' => $event_data['current_year'],
      '#current_month' => $event_data['current_month'],
      '#attached' => [
        'library' => [
          'event_archive/form',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'url.path', 'url.query_args'
        ]
      ],
    ];
  }

}
