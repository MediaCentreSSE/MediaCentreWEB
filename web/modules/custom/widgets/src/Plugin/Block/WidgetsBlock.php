<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\widgets\WidgetManager;

/**
 * Provides a 'WidgetsBlock' block.
 *
 * @Block(
 *   id = "widgets_block",
 *   admin_label = @Translation("Widgets block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class WidgetsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\widgets\WidgetManager definition.
   *
   * @var \Drupal\widgets\WidgetManager
   */
  protected $widgetManager;

  /**
   * Constructs a new WidgetsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\widgets\WidgetManager $widget_manager
   *   The widget manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    WidgetManager $widget_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('widgets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $widgets = $this->widgetManager->getNodeWidgets($node);

    return [
      '#theme' => 'widgets_block',
      '#widgets' => $widgets,
    ];
  }

}
