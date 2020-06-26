<?php

namespace Drupal\twig_addons\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group twig_addons
 */
class LoadTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twig_addons'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

}
