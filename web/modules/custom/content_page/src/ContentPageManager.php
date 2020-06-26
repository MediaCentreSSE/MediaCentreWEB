<?php

namespace Drupal\content_page;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContentPageManager.
 */
class ContentPageManager {

  /**
   * Renderable menu system name.
   *
   * @var string
   */
  const MENU_NAME = 'main';

  /**
   * Menu creation manipulator array.
   *
   * @var array
   */
  const MENU_MANIPULATORS = [
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];

  /**
   * Content type's "Frontpage" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_FRONTPAGE = 'frontpage';

  /**
   * Content type's "News" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_NEWS = 'news';

  /**
   * Content type's "News list" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_NEWS_LIST = 'news_list';

  /**
   * Content type's "Event" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_EVENT = 'event';

  /**
   * Content type's "Event list" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_EVENT_LIST = 'event_list';

  /**
   * Content type's "Category page" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_CATEGORY_PAGE = 'category_page';

  /**
   * Content type's "Content page" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_CONTENT_PAGE = 'content_page';

  /**
   * Content type's "Administration" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_ADMINISTRATION = 'administration';

  /**
   * Content type's "Faculty" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_FACULTY = 'faculty';

  /**
   * Content type's "Contacts" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_CONTACTS = 'contacts';

  /**
   * Content type's "Staff" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_STAFF = 'staff';

  /**
   * Content type's "Search results" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_SEARCH_RESULTS = 'search_results';

  /**
   * Content type's "Blog" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_BLOG = 'blog';

  /**
   * Content type's "Blog list" machine name.
   *
   * @var string
   */
  const CONTENT_TYPE_BLOG_LIST = 'blog_list';

  /**
   * Drupal\Core\Menu\MenuLinkTreeInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Drupal\Core\Menu\MenuLinkManagerInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentPageManager object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The plugin manager menu link.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    MenuLinkTreeInterface $menu_link_tree,
    MenuLinkManagerInterface $menu_link_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Loads a menu link by node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent|null
   *   Menu link plugin or NULL.
   */
  public function getMenuLinkByNode(Node $node) {
    // Load all menu items, which route node parameter matches current node.
    $menu_links = $this->menuLinkManager->loadLinksByRoute(
      'entity.node.canonical',
      ['node' => $node->id()],
      self::MENU_NAME
    );

    return count($menu_links) ? reset($menu_links) : NULL;
  }

  /**
   * Builds a menu from menu link.
   *
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link
   *   The menu link.
   * @param \Drupal\node\Entity\Node $active_node
   *   The active node.
   *
   * @return array
   *   The menu from menu link.
   */
  public function getMenuFromMenuLink(MenuLinkContent $menu_link, Node $active_node = NULL) {
    // If no custom active node passed, use current route's 'node' parameter.
    if (!$active_node) {
      $menu_parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters(self::MENU_NAME);
    }
    else {
      $active_node_plugin_id = $this->getMenuLinkByNode($active_node)->getPluginId();
      $menu_parameters = new MenuTreeParameters();

      // If passed active node has a menu link, mark it as the active trail.
      if ($active_node_plugin_id) {
        $menu_parameters->setActiveTrail([$active_node_plugin_id => $active_node_plugin_id]);
      }
    }

    // Set menu parameters - 1 level deep; from matching menu item; skip root menu item output.
    $menu_parameters->setMaxDepth(1);
    $menu_parameters->setRoot($menu_link->getPluginId());
    $menu_parameters->excludeRoot();

    // Build menu.
    $tree = $this->menuLinkTree->load(self::MENU_NAME, $menu_parameters);
    $tree = $this->menuLinkTree->transform($tree, self::MENU_MANIPULATORS);
    $menu = $this->menuLinkTree->build($tree);

    return $menu;
  }

  /**
   * Gets the menu link by plugin identifier.
   *
   * @param string $plugin_id
   *   The plugin identifier.
   *
   * @return \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent
   *   The menu link.
   */
  public function getMenuLinkByPluginId($plugin_id) {
    return $this->menuLinkManager->createInstance($plugin_id);
  }

  /**
   * Gets the node from menu link.
   *
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link
   *   The menu link.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Node or NULL.
   */
  public function getNodeFromMenuLink(MenuLinkContent $menu_link) {
    $plugin_definition = $menu_link->getPluginDefinition();

    // If menu link's route is not a standard node route, node can not be
    // retrieved (easily).
    if ($plugin_definition['route_name'] != 'entity.node.canonical') {
      return NULL;
    }

    return Node::load($plugin_definition['route_parameters']['node']);
  }

  /**
   * Gets the parent node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The parent node or NULL.
   */
  public function getParentNode(Node $node) {
    switch ($node->getType()) {
      // News page's parent is always its news list page.
      case self::CONTENT_TYPE_NEWS:
        return $node->get('field_news_list')->target_id
          ? $node->get('field_news_list')->entity
          : NULL;

      // Event page's parent is always its event list page.
      case self::CONTENT_TYPE_EVENT:
        return $node->get('field_event_list')->target_id
          ? $node->get('field_event_list')->entity
          : NULL;

      // Blog page's parent is always its blog list page.
      case self::CONTENT_TYPE_BLOG:
        return $node->get('field_blog_list')->target_id
          ? $node->get('field_blog_list')->entity
          : NULL;

      case self::CONTENT_TYPE_STAFF:
        if ($node->get('field_administration')->value && ($administration_node = $this->getAdministrationNode())) {
          return $administration_node;
        }
        elseif ($node->get('field_faculty')->value && ($faculty_node = $this->getFacultyNode())) {
          return $faculty_node;
        }
        break;

      // Content page, that is an overview for a category page, uses the
      // category as it's parent.
      default:
        if ($overviewed_node = $this->getOverviewedNode($node)) {
          return $overviewed_node;
        }
        break;
    }

    // Load the first matching menu item.
    $menu_link = $this->getMenuLinkByNode($node);

    // If no matching menu item found.
    if (!$menu_link) {
      return NULL;
    }

    // Get parent plugin ID. Because menu items are plugins and child items
    // are linked with their parents by this plugin ID.
    $parent_plugin_id = $menu_link->getParent();
    if (!$parent_plugin_id) {
      return NULL;
    }

    // Load parent menu item by its plugin ID. Because normal entity IDs are
    // just too mainstream and life is too easy.
    $parent_menu_link = $this->getMenuLinkByPluginId($parent_plugin_id);

    // Extract node from parent menu link.
    return $this->getNodeFromMenuLink($parent_menu_link);
  }

  /**
   * Gets menu item sort weight from parent menu item child count.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContentEntity $menu_link
   *   The menu link.
   *
   * @return int
   *   Sort value.
   */
  public function getMenuItemWeight(MenuLinkContentEntity $menu_link) {
    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMaxDepth(1);
    $menu_parameters->setRoot($menu_link->getPluginId());
    $menu_parameters->excludeRoot();

    // Build menu.
    $tree = $this->menuLinkTree->load(self::MENU_NAME, $menu_parameters);
    $tree = $this->menuLinkTree->transform($tree, self::MENU_MANIPULATORS);
    $menu = $this->menuLinkTree->build($tree);

    return isset($menu['#items']) ? count($menu['#items']) : 0;
  }

  /**
   * Gets the overviewed node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The overview page node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The overviewed node.
   */
  private function getOverviewedNode(Node $node) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $overviewed_node_ids = $query
      ->condition('type', self::CONTENT_TYPE_CATEGORY_PAGE)
      ->condition('field_overview_page', $node->id())
      ->range(0, 1)
      ->execute();

    return count($overviewed_node_ids) ? Node::load(reset($overviewed_node_ids)) : NULL;
  }

  /**
   * Gets the administration node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The overviewed node.
   */
  private function getAdministrationNode() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $administration_ids = $query
      ->condition('type', self::CONTENT_TYPE_ADMINISTRATION)
      ->range(0, 1)
      ->execute();

    return count($administration_ids) ? Node::load(reset($administration_ids)) : NULL;
  }

  /**
   * Gets the administration node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The overviewed node.
   */
  private function getFacultyNode() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $faculty_ids = $query
      ->condition('type', self::CONTENT_TYPE_FACULTY)
      ->range(0, 1)
      ->execute();

    return count($faculty_ids) ? Node::load(reset($faculty_ids)) : NULL;
  }

  /**
   * Builds a single menu link into a menu item.
   *
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link
   *   The menu link.
   * @param bool $active
   *   Determines if buildable menu link should be active.
   *
   * @return array
   *   The built menu link.
   */
  public function buildMenuLink(MenuLinkContent $menu_link, $active = FALSE) {
    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMaxDepth(0);
    $menu_parameters->setRoot($menu_link->getPluginId());

    if ($active) {
      $menu_parameters->setActiveTrail([$menu_link->getPluginId() => $menu_link->getPluginId()]);
    }

    $tree = $this->menuLinkTree->load(self::MENU_NAME, $menu_parameters);
    $tree = $this->menuLinkTree->transform($tree, self::MENU_MANIPULATORS);
    $menu = $this->menuLinkTree->build($tree);

    return count($menu['#items'] ?? []) ? reset($menu['#items']) : NULL;
  }

}
