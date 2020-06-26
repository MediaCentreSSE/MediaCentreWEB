<?php

namespace Drupal\research\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\research\ResearchManager;

/**
 * Provides a 'ResearchBlock' block.
 *
 * @Block(
 *   id = "research_block",
 *   admin_label = @Translation("Research block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class ResearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\research\ResearchManager definition.
   *
   * @var \Drupal\research\ResearchManager
   */
  protected $researchManager;

  /**
   * Constructs a new ResearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\research\ResearchManager $research_manager
   *   The research manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ResearchManager $research_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->researchManager = $research_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('research.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $research_data = $this->researchManager->getResearchItems($node);
    $pager = pager_default_initialize($research_data['total'], ResearchManager::PAGE_SIZE);

    return [
      '#theme' => 'research_block',
      '#items' => $research_data['items'],
      '#node' => $node,
    ];
  }

}
