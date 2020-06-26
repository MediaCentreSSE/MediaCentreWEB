<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'HighlightBlock' block.
 *
 * @Block(
 *  id = "highlight_block",
 *  admin_label = @Translation("Highlight block"),
 * )
 */
class HighlightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'highlight_block',
      '#content' => $content,
    ];
  }

}
