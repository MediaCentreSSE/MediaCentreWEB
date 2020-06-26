<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'QuickLinksGrid' block.
 *
 * @Block(
 *   id = "quick_links_grid",
 *   admin_label = @Translation("Quick links grid"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class QuickLinksGrid extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'quick_links_grid_block',
      '#items' => $node ? $node->field_quick_links_grid->referencedEntities() : [],
    ];
  }

}
