<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'QuoteBlock' block.
 *
 * @Block(
 *  id = "quote_block",
 *  admin_label = @Translation("Quote block"),
 * )
 */
class QuoteBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'quote_block',
      '#content' => $content,
    ];
  }

}
