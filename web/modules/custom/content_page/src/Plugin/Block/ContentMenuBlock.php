<?php

namespace Drupal\content_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_page\ContentPageManager;

/**
 * Provides a 'ContentMenuBlock' block.
 *
 * @Block(
 *   id = "content_menu_block",
 *   admin_label = @Translation("Content menu block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class ContentMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\content_page\ContentPageManager definition.
   *
   * @var \Drupal\content_page\ContentPageManager
   */
  protected $contentPageManager;

  /**
   * Constructs a new ContentMenuBlock object.
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
    $active_node = NULL;
    $prependable_menu_item = NULL;

    // If page doesn't require this menu menu, return empty build array.
    if (
      !$node->hasField('field_content_menu')
      || !$node->get('field_content_menu')->value
    ) {
      return $this->getEmptyBuildArray();
    }

    // If current node is a news page, use its news list page to get this menu.
    // Act as if the current active menu item is the news list page.
    if ($node->getType() == ContentPageManager::CONTENT_TYPE_NEWS) {
      if (!$node->get('field_news_list')->target_id) {
        return $this->getEmptyBuildArray();
      }

      $active_node = $node = $node->get('field_news_list')->entity;
    }
    // If current node is a staff page, use its faculty/administration page.
    elseif ($node->getType() == ContentPageManager::CONTENT_TYPE_STAFF) {
      $active_node = $node = $this->contentPageManager->getParentNode($node);
    }

    // Load the first matching menu item.
    $menu_link = $this->contentPageManager->getMenuLinkByNode($node);

    // If no matching menu item found, return empty build array.
    if (!$menu_link) {
      return $this->getEmptyBuildArray();
    }

    // If sibling menu required, get parent menu item of current page.
    if ($node->get('field_content_menu')->value == 'sibling') {
      $parent_plugin_id = $menu_link->getParent();
      if (!$parent_plugin_id) {
        return $this->getEmptyBuildArray();
      }

      // Load parent menu item by its plugin ID. Because normal entity IDs are
      // just too mainstream and life is too easy.
      $menu_link = $this->contentPageManager->getMenuLinkByPluginId($parent_plugin_id);
      if (!$menu_link) {
        return $this->getEmptyBuildArray();
      }

      $parent_node = $this->contentPageManager->getNodeFromMenuLink($menu_link);
      if ($parent_node->getType() != ContentPageManager::CONTENT_TYPE_CATEGORY_PAGE) {
        $prependable_menu_item = $this->contentPageManager->buildMenuLink($menu_link);
      }
    }
    else {
      $prependable_menu_item = $this->contentPageManager->buildMenuLink($menu_link, TRUE);
    }

    // Build a menu array.
    $menu = $this->contentPageManager->getMenuFromMenuLink($menu_link, $active_node);
    if ($prependable_menu_item && ($menu['#items'] ?? [])) {
      array_unshift($menu['#items'], $prependable_menu_item);
    }

    return [
      '#theme' => 'content_menu_block',
      '#items' => $menu['#items'] ?? [],
      '#cache' => $menu['#cache'],
    ];
  }

  /**
   * Gets an empty build array.
   *
   * @return array
   *   An empty build array.
   */
  private function getEmptyBuildArray() {
    return [
      '#markup' => '',
    ];
  }

}
