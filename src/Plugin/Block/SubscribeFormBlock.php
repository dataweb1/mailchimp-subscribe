<?php

namespace Drupal\mailchimp_subscribe\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subscribe' Block.
 *
 * @Block(
 *   id = "subscribe_form_block",
 *   admin_label = @Translation("Subscribe Form block"),
 *   category = @Translation("Mailchimp Subscribe Form"),
 * )
 */
class SubscribeFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'subscribe_form_block',
    ];
  }

}
