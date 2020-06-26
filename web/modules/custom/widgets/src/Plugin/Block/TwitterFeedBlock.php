<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use TwitterAPIExchange;

/**
 * Provides a 'TwitterFeedBlock' block.
 *
 * @Block(
 *   id = "twitter_feed_block",
 *   admin_label = @Translation("Twitter feed block"),
 * )
 */
class TwitterFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new TwitterFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];
    $tweets = $this->getTweets($content->get('field_twitter_account_name')->value, $content->get('field_tweet_count')->value);

    return [
      '#theme' => 'twitter_feed_block',
      '#content' => $content,
      '#tweets' => $tweets,
    ];
  }

  /**
   * Gets the tweets.
   *
   * @param string $account_name
   *   The Twitter account name.
   * @param int $tweet_count
   *   The tweet count.
   *
   * @return array
   *   The tweets.
   */
  private function getTweets($account_name, $tweet_count) {
    $config = $this->configFactory->get('social_network.settings');

    if (
      !$account_name
      || !$config->get('twitter_consumer_key')
      || !$config->get('twitter_consumer_secret')
      || !$config->get('twitter_access_token')
      || !$config->get('twitter_access_token_secret')
    ) {
      return [];
    }

    $settings = [
      'consumer_key' => $config->get('twitter_consumer_key'),
      'consumer_secret' => $config->get('twitter_consumer_secret'),
      'oauth_access_token' => $config->get('twitter_access_token'),
      'oauth_access_token_secret' => $config->get('twitter_access_token_secret'),
    ];

    $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $getfield = '?screen_name=' . $account_name . '&count=' . $tweet_count;
    $requestMethod = 'GET';

    $twitter = new TwitterAPIExchange($settings);
    $response = $twitter->setGetfield($getfield)
      ->buildOauth($url, $requestMethod)
      ->performRequest();

    $response = json_decode($response);

    if (!is_array($response)) {
      return [];
    }

    return $response;
  }

}
