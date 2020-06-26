<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'QuickLinksSlider' block.
 *
 * @Block(
 *   id = "quick_links_slider",
 *   admin_label = @Translation("Quick links slider"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class QuickLinksSlider extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'quick_links_slider_block',
      '#items' => $node ? $node->field_quick_links_slides->referencedEntities() : [],
      '#attached' => [
        'library' => [
          'frontpage/quick_links_slider',
        ],
      ],
    ];
  }

}
