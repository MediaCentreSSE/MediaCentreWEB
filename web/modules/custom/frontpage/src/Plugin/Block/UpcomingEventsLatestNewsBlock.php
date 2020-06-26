<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\event_list\EventListManager;
use Drupal\news_list\NewsListManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'UpcomingEventsLatestNewsBlock' block.
 *
 * @Block(
 *   id = "upcoming_events_latest_news_block",
 *   admin_label = @Translation("Upcoming Events & Latest News block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class UpcomingEventsLatestNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\event_list\EventListManager definition.
   *
   * @var \Drupal\event_list\EventListManager
   */
  protected $eventListManager;

  /**
   * Drupal\news_list\NewsListManager definition.
   *
   * @var \Drupal\news_list\NewsListManager
   */
  protected $newsListManager;

  /**
   * Constructs a new FacultyListBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\event_list\EventListManager $event_list_manager
   *   The event list manager.
   * @param \Drupal\news_list\NewsListManager $news_list_manager
   *   The news list manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EventListManager $event_list_manager,
    NewsListManager $news_list_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventListManager = $event_list_manager;
    $this->newsListManager = $news_list_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_list.manager'),
      $container->get('news_list.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    $events = $node->get('field_upcoming_events_list')->target_id
      ? $this->eventListManager->getUpcomingEvents($node->get('field_upcoming_events_list')->target_id)
      : [];

    $news = $node->get('field_latest_news_list')->target_id
      ? $this->newsListManager->getLatestNews($node->get('field_latest_news_list')->target_id)
      : [];

    return [
      '#theme' => 'upcoming_events_latest_news_block',
      '#events' => $events,
      '#news' => $news,
    ];
  }

}
