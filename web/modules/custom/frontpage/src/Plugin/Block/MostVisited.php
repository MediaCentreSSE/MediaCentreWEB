<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'MostVisited' block.
 *
 * @Block(
 *   id = "most_visited",
 *   admin_label = @Translation("Most visited block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class MostVisited extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'most_visited_block',
      '#items' => $node ? $node->field_most_visited_columns->referencedEntities() : [],
    ];
  }

}
