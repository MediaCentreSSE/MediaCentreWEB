<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'QuickLinksSocial' block.
 *
 * @Block(
 *   id = "quick_links_social",
 *   admin_label = @Translation("Quick links social"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class QuickLinksSocial extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'quick_links_social_block',
      '#items' => $node ? $node->field_quick_links_social->referencedEntities() : [],
    ];
  }

}
