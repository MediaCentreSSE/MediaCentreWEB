<?php

namespace Drupal\breadcrumbs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_page\ContentPageManager;

/**
 * Provides a 'BreadcrumbsBlock' block.
 *
 * @Block(
 *   id = "breadcrumbs_block",
 *   admin_label = @Translation("Breadcrumbs block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class BreadcrumbsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\content_page\ContentPageManager definition.
   *
   * @var \Drupal\content_page\ContentPageManager
   */
  protected $contentPageManager;

  /**
   * Constructs a new BreadcrumbsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_page\ContentPageManager $content_page_manager
   *   The content page manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContentPageManager $content_page_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentPageManager = $content_page_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_page.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $items = [];

    if ($node->getType() != ContentPageManager::CONTENT_TYPE_FRONTPAGE) {
      while ($node) {
        if ($node->getType() == ContentPageManager::CONTENT_TYPE_CATEGORY_PAGE) {
          $node->unlinkable = TRUE;
        }
        array_unshift($items, $node);
        $node = $this->contentPageManager->getParentNode($node);
      }
    }

    return [
      '#theme' => 'breadcrumbs_block',
      '#items' => $items,
    ];
  }

}
