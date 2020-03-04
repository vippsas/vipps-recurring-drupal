<?php

/**
 * @file
 * Contains monthly_charges.page.inc.
 *
 * Page callback for Monthly charges entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Monthly charges templates.
 *
 * Default template: monthly_charges.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_monthly_charges(array &$variables) {
  // Fetch MonthlyCharges Entity Object.
  $monthly_charges = $variables['elements']['#monthly_charges'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
