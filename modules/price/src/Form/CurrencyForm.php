<?php

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class CurrencyForm extends EntityForm {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a new CurrencyForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The currency storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $this->entity;

    $import_url = Url::fromRoute('entity.commerce_currency.import')->toString();
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('This form is only intended for creating custom currencies. Real-world currencies should be <a href=":url">imported</a>.', [':url' => $import_url]),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $currency->getName(),
      '#placeholder' => $this->t('Custom Currency Name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $iso_4217_url = Url::fromUri('https://en.wikipedia.org/wiki/ISO_4217#Active_codes')->toString();
    $form['currencyCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('The three letter code, as defined by <a href=":url" target="_blank">ISO 4217</a>.', [':url' => $iso_4217_url]),
      '#default_value' => $currency->getCurrencyCode(),
      '#element_validate' => ['::validateCurrencyCode'],
      '#pattern' => '[A-Z]{3}',
      '#placeholder' => 'USD',
      '#maxlength' => 3,
      '#size' => 4,
      '#disabled' => !$currency->isNew(),
      '#required' => TRUE,
    ];
    $form['numericCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numeric code'),
      '#description' => $this->t('The three digit code, as defined by <a href=":url" target="_blank">ISO 4217</a>.', [':url' => $iso_4217_url]),
      '#default_value' => $currency->getNumericCode(),
      '#element_validate' => ['::validateNumericCode'],
      '#pattern' => '[\d]{3}',
      '#placeholder' => '999',
      '#maxlength' => 3,
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#description' => $this->t('Currency symbol used to denote that the number is a monetary value.'),
      '#default_value' => $currency->getSymbol(),
      '#placeholder' => '$',
      '#maxlength' => 4,
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['fractionDigits'] = [
      '#type' => 'number',
      '#title' => $this->t('Fraction digits'),
      '#description' => $this->t('Decimal places: the number of digits after the decimal sign. ie. 125.00'),
      '#default_value' => $currency->getFractionDigits(),
      '#min' => 0,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Validates the currency code.
   */
  public function validateCurrencyCode(array $element, FormStateInterface $form_state, array $form) {
    $currency = $this->getEntity();
    $currency_code = $element['#value'];
    if (!preg_match('/^[A-Z]{3}$/', $currency_code)) {
      $form_state->setError($element, $this->t('The currency code must consist of three uppercase letters.'));
    }
    elseif ($currency->isNew()) {
      $loaded_currency = $this->storage->load($currency_code);
      if ($loaded_currency) {
        $form_state->setError($element, $this->t('The currency code is already in use.'));
      }
    }
  }

  /**
   * Validates the numeric code.
   */
  public function validateNumericCode(array $element, FormStateInterface $form_state, array $form) {
    $currency = $this->getEntity();
    $numeric_code = $element['#value'];
    if ($numeric_code && !preg_match('/^\d{3}$/i', $numeric_code)) {
      $form_state->setError($element, $this->t('The numeric code must consist of three digits.'));
    }
    elseif ($currency->isNew()) {
      $loaded_currencies = $this->storage->loadByProperties([
        'numericCode' => $numeric_code,
      ]);
      if ($loaded_currencies) {
        $form_state->setError($element, $this->t('The numeric code is already in use.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;
    $currency->save();
    drupal_set_message($this->t('Saved the %label currency.', [
      '%label' => $currency->label(),
    ]));
    $form_state->setRedirect('entity.commerce_currency.collection');
  }

}
