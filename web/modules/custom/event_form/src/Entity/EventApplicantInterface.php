<?php

namespace Drupal\event_form\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event applicant entities.
 *
 * @ingroup event_form
 */
interface EventApplicantInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Event applicant name.
   *
   * @return string
   *   Name of the Event applicant.
   */
  public function getName();

  /**
   * Sets the Event applicant name.
   *
   * @param string $name
   *   The Event applicant name.
   *
   * @return \Drupal\event_form\Entity\EventApplicantInterface
   *   The called Event applicant entity.
   */
  public function setName($name);

  /**
   * Gets the Event applicant creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event applicant.
   */
  public function getCreatedTime();

  /**
   * Sets the Event applicant creation timestamp.
   *
   * @param int $timestamp
   *   The Event applicant creation timestamp.
   *
   * @return \Drupal\event_form\Entity\EventApplicantInterface
   *   The called Event applicant entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Event applicant published status indicator.
   *
   * Unpublished Event applicant are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event applicant is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event applicant.
   *
   * @param bool $published
   *   TRUE to set this Event applicant to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\event_form\Entity\EventApplicantInterface
   *   The called Event applicant entity.
   */
  public function setPublished($published);

}
