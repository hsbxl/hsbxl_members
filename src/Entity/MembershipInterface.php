<?php

namespace Drupal\hsbxl_members\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Membership entities.
 *
 * @ingroup hsbxl_members
 */
interface MembershipInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Membership name.
   *
   * @return string
   *   Name of the Membership.
   */
  public function getName();

  /**
   * Sets the Membership name.
   *
   * @param string $name
   *   The Membership name.
   *
   * @return \Drupal\hsbxl_members\Entity\MembershipInterface
   *   The called Membership entity.
   */
  public function setName($name);

  /**
   * Gets the Membership creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Membership.
   */
  public function getCreatedTime();

  /**
   * Sets the Membership creation timestamp.
   *
   * @param int $timestamp
   *   The Membership creation timestamp.
   *
   * @return \Drupal\hsbxl_members\Entity\MembershipInterface
   *   The called Membership entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Membership published status indicator.
   *
   * Unpublished Membership are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Membership is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Membership.
   *
   * @param bool $published
   *   TRUE to set this Membership to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\hsbxl_members\Entity\MembershipInterface
   *   The called Membership entity.
   */
  public function setPublished($published);

}
