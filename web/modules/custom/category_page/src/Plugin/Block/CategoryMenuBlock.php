<?php

namespace Drupal\category_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_page\ContentPageManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a 'CategoryMenuBlock' block.
 *
 * @Block(
 *   id = "category_menu_block",
 *   admin_label = @Translation("Category menu block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class CategoryMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\content_page\ContentPageManager definition.
   *
   * @var \Drupal\content_page\ContentPageManager
   */
  protected $contentPageManager;

  /**
   * Constructs a new CategoryMenuBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_page\ContentPageManager $contentPageManager
   *   The content page manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContentPageManager $contentPageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentPageManager = $contentPageManager;
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
    // Category pages disabled from now on. Redirect away.
    return $this->redirectAway();

    $node = $this->getContextValue('node');
    $menu_link = $this->contentPageManager->getMenuLinkByNode($node);

    // If no matching menu item found, skip menu output.
    if (!$menu_link) {
      return [
        '#markup' => '',
      ];
    }

    // Build a menu array.
    $menu = $this->contentPageManager->getMenuFromMenuLink($menu_link);

    return [
      '#theme' => 'category_menu_block',
      '#items' => $menu['#items'] ?? [],
      '#cache' => $menu['#cache'],
    ];
  }

  /**
   * Redirect away. 301 redirect used, hopefully, change is permanenet.
   */
  private function redirectAway() {
    $response = new RedirectResponse('/');
    $response->send();
  }

}
