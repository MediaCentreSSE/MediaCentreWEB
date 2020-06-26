<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Tour360Block' block.
 *
 * @Block(
 *  id = "tour_360_block",
 *  admin_label = @Translation("360 Tour block"),
 * )
 */
class Tour360Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'tour_360_block',
      '#content' => $content,
    ];
  }

}
