<?php

namespace Drupal\widgets;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\content_page\ContentPageManager;
use Drupal\node\Entity\Node;

/**
 * Class WidgetManager.
 */
class WidgetManager {

  /**
   * Drupal\Core\Block\BlockManager definition.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\content_page\ContentPageManager definition.
   *
   * @var \Drupal\content_page\ContentPageManager
   */
  protected $contentPageManager;

  /**
   * Constructs a new WidgetManager object.
   */
  public function __construct(
    BlockManager $plugin_manager_block,
    AccountProxy $current_user,
    ContentPageManager $content_page_manager
  ) {
    $this->blockManager = $plugin_manager_block;
    $this->currentUser = $current_user;
    $this->contentPageManager = $content_page_manager;
  }

  /**
   * Gets the node widgets.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   *
   * @return array
   *   The node widgets.
   */
  public function getNodeWidgets(Node $node) {
    // Get all inherited widgets from all ancestors.
    $widgets = $this->getInheritedWidgets($node);

    // Append current node's widgets.
    if ($node->hasField('field_widgets')) {
      $custom_blocks = $node->get('field_widgets')->referencedEntities();
      $widgets = $this->renderWidgets($custom_blocks, $widgets);
    }

    // Prepend private widget.
    if ($node->hasField('field_private_widget_text') && $node->get('field_private_widget_text')->value) {
      $private_widget = [
        '#theme' => 'private_widget_block',
        '#content' => [
          'title' => $node->get('field_private_widget_title')->value,
          'text' => $node->get('field_private_widget_text')->value,
          'background' => $node->hasField('field_private_widget_background') ? $node->get('field_private_widget_background')->value : '',
        ],
        '#max-age' => '600',
      ];

      array_unshift($widgets, $private_widget);
    }

    return $widgets;
  }

  /**
   * Gets the inherited widgets.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param array $widgets
   *   Current widgets.
   * @param bool $render
   *   Render widgets or just return content blocks.
   *
   * @return array
   *   The inherited widgets.
   */
  public function getInheritedWidgets(Node $node, array $widgets = [], $render = TRUE) {
    // If widget inheritance not available, return.
    if (!$node->hasField('field_inherit_widgets') || !$node->get('field_inherit_widgets')->value) {
      return $widgets;
    }

    // If node has no parent node, return.
    $parent_node = $this->contentPageManager->getParentNode($node);
    if (!$parent_node) {
      return $widgets;
    }

    // Get all ancestor widgets.
    $widgets = $this->getInheritedWidgets($parent_node, $widgets);

    // If parent node has a widget field, add its widgets to current widgets.
    if ($parent_node->hasField('field_widgets')) {
      $custom_blocks = $parent_node->get('field_widgets')->referencedEntities();
      $widgets = $render ? $this->renderWidgets($custom_blocks, $widgets) : $custom_blocks;
    }

    return $widgets;
  }

  /**
   * Renders widget build arrays from custom blocks.
   *
   * @param array $custom_blocks
   *   The custom blocks.
   * @param array $widgets
   *   The widgets.
   *
   * @return array
   *   Array of widget build arrays.
   */
  private function renderWidgets(array $custom_blocks, array $widgets = []) {
    foreach ($custom_blocks as $custom_block) {
      // Assign an output block based on custom block type.
      switch ($custom_block->get('type')->target_id) {
        case 'poll':
          $block_id = 'poll_block';
          break;

        case 'additional_links':
          $block_id = 'additional_links_block';
          break;

        case 'facebook_feed':
          $block_id = 'facebook_feed_block';
          break;

        case 'contact_person':
          $block_id = 'contact_person_block';
          break;

        case 'twitter_feed':
          $block_id = 'twitter_feed_block';
          break;

        case 'button':
          $block_id = 'button_block';
          break;

        case 'highlight':
          $block_id = 'highlight_block';
          break;

        case 'quote':
          $block_id = 'quote_block';
          break;

        case '360_tour':
          $block_id = 'tour_360_block';
          break;

        case 'visit_us':
          $block_id = 'visit_us_block';
          break;

        case 'block_as_link':
          $block_id = 'block_as_link_block';
          break;

        case 'freeform':
        default:
          $block_id = 'freeform_block';
          break;
      }

      // Create configuration parameters for block instance.
      $config = ['content' => $custom_block];

      // Create block instance.
      $block = $this->blockManager->createInstance($block_id, $config);

      // Skip block, if current user has no access to it.
      if (!$block->access($this->currentUser)) {
        continue;
      }

      // Render block into a build array.
      $widgets[] = $block->build();
    }

    return $widgets;
  }

}
