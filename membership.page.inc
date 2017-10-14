<?php

/**
 * @file
 * Contains membership.page.inc.
 *
 * Page callback for Membership entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Membership templates.
 *
 * Default template: membership.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_membership(array &$variables) {
  // Fetch Membership Entity Object.
  $membership = $variables['elements']['#membership'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
