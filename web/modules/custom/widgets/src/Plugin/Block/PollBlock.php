<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PollBlock' block.
 *
 * @Block(
 *  id = "poll_block",
 *  admin_label = @Translation("Poll block"),
 * )
 */
class PollBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];
    $poll = $content->get('field_poll')->entity;
    $output = entity_view($poll, 'block');

    return [
      '#theme' => 'poll_block',
      '#title' => $content->get('field_title')->value,
      '#poll' => $output,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
