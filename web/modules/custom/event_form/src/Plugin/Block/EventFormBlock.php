<?php

namespace Drupal\event_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\event_form\EventFormManager;

/**
 * Provides a 'EventFormBlock' block.
 *
 * @Block(
 *   id = "event_form_block",
 *   admin_label = @Translation("Event form block")
 * )
 */
class EventFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\event_form\EventFormManager definition.
   *
   * @var \Drupal\event_form\EventFormManager
   */
  protected $eventFormManager;

  /**
   * Constructs a new EventFormBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\event_form\EventFormManager $event_form_manager
   *   The event form manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EventFormManager $event_form_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventFormManager = $event_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_form.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $node = $config['node'] ?? NULL;
    if (!$node || !$node->hasField('field_event_form') || !$node->get('field_event_form')->target_id) {
      return [
        '#markup' => '',
      ];
    }

    $content = $node->get('field_event_form')->entity;
    $form = $this->eventFormManager->getEventForm($node);
    $message = $this->eventFormManager->getMessage();

    return [
      '#theme' => 'event_form_block',
      '#form' => $form,
      '#message' => $message,
      '#content' => $content,
      '#attached' => [
        'library' => [
          'event_form/event_form'
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
