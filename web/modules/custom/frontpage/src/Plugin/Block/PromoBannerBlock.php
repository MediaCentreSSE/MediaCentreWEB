<?php

namespace Drupal\frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PromoBannerBlock' block.
 *
 * @Block(
 *   id = "promo_banner_block",
 *   admin_label = @Translation("Promo banner block"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class PromoBannerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    return [
      '#theme' => 'promo_banner_block',
      '#node' => $node ?: NULL,
      '#attached' => [
        'library' => [
          'frontpage/quick_links_slider',
        ],
      ],
    ];
  }

}
