<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FreeformBlock' block.
 *
 * @Block(
 *  id = "freeform_block",
 *  admin_label = @Translation("Freeform block"),
 * )
 */
class FreeformBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'freeform_block',
      '#content' => $content,
    ];
  }

}
