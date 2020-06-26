<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'QuickLinksPromoted' block.
 *
 * @Block(
 *   id = "quick_links_promoted",
 *   admin_label = @Translation("Quick links promoted"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class QuickLinksPromoted extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'quick_links_promoted_block',
      '#items' => $node ? $node->field_quick_links_promoted->referencedEntities() : [],
      '#embed_code' => $node ? $node->field_embed_code->value : '',
    ];
  }

}
