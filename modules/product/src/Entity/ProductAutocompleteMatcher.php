<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class ProductAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * The inner autocomplete matcher service.
   *
   * @var \Drupal\Core\Entity\EntityAutocompleteMatcher
   */
  protected $entityAutocompleteMatcher;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $entity_autocomplete_matcher, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory, SelectionPluginManagerInterface $selection_manager) {
    $this->entityAutocompleteMatcher = $entity_autocomplete_matcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->numberFormatter = $number_formatter_factory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(6);

    parent::__construct($selection_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = $this->entityAutocompleteMatcher->getMatches($target_type, $selection_handler, $selection_settings, $string);

    if (!empty($matches) && $target_type == 'commerce_product_variation') {
      $options = [
        'target_type' => $target_type,
        'handler' => $selection_handler,
        'handler_settings' => $selection_settings,
      ];
      $handler = $this->selectionManager->getInstance($options);

      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);
      $ctr = 0;

      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product */
          $product = $this->entityTypeManager->getStorage($target_type)
            ->load($entity_id);

          /** @var \Drupal\commerce_price\Price $price */
          $price = $product->getPrice();
          $currency = $this->entityTypeManager->getStorage('commerce_currency')
            ->load($price->getCurrencyCode());

          $matches[$ctr++]['value'] .= ' - ' . $this->numberFormatter->formatCurrency($price->getNumber(), $currency);
        }
      }
    }

    return $matches;
  }

}
