<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
  exit;
}

/**
 * Class Platon
 */
class Platon extends PaymentModule {
  const CONFIGURATION_KEY         = 'PLATON_KEY';
  const CONFIGURATION_PASSWORD    = 'PLATON_PASSWORD';
  const CONFIGURATION_GATEWAY_URL = 'PLATON_GATEWAY_URL';

  const DEFAULT_GATEWAY_URL = 'https://secure.platononline.com/payment/auth';
  const FIELD_KEY           = 'key';
  const FIELD_ORDER         = 'order';
  const FIELD_URL           = 'url';
  const FIELD_ERROR_URL     = 'error_url';
  const FIELD_SIGN          = 'sign';
  const FIELD_DATA          = 'data';

  const KEY_VALUE = 'value';

  /**
   * Allowed configuration settings
   *
   * @var array
   */
  private static $configuration = [
      'Platon Key'      => self::CONFIGURATION_KEY,
      'Platon Password' => self::CONFIGURATION_PASSWORD,
      'Gateway URL'     => self::CONFIGURATION_GATEWAY_URL,
  ];

  /**
   * Platon constructor.
   */
  public function __construct() {
    $this->name                   = 'platon';
    $this->tab                    = 'payments_gateways';
    $this->version                = '1.0.0';
    $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    $this->author                 = 'Platon';
    $this->controllers            = ['callback'];

    $this->currencies      = true;
    $this->currencies_mode = 'checkbox';
    $this->bootstrap       = true;

    parent::__construct();

    $this->displayName = $this->l('Platon Payment Gateway');
    $this->description = $this->l('Credit cards payment processor.');

    if (!count(Currency::checkPaymentCurrencies($this->id))) {
      $this->warning = $this->l('No currency has been set for this module.');
    }

    $config = Configuration::getMultiple(static::$configuration);
    foreach (static::$configuration as $name => $key) {
      if (empty($config[$key])) {
        $this->warning = $this->l('Module must be configured before using');
      }
    }
  }

  /**
   * @return bool
   */
  public function install() {
    if (!parent::install()
        || !$this->registerHook('paymentOptions')
        || !$this->registerHook('paymentReturn')
        || !Configuration::updateValue(self::CONFIGURATION_GATEWAY_URL, self::DEFAULT_GATEWAY_URL)
    ) {
      return false;
    }

    return true;
  }

  /**
   * @return bool
   */
  public function uninstall() {
    if (!parent::uninstall() || !$this->deleteConfiguration()) {
      return false;
    }

    return true;
  }

  /**
   * @param $params
   *
   * @return array|null
   */
  public function hookPaymentOptions($params) {
    if (!$this->active) {
      return null;
    }

    /** @var Cart $cart */
    $cart = $params['cart'];

    if (!$this->checkCurrency($cart)) {
      return null;
    }

    $externalOption = new PaymentOption();
    $externalOption->setCallToActionText($this->l('Pay with Platon'))
        ->setAction(Configuration::get(self::CONFIGURATION_GATEWAY_URL))
        ->setInputs($this->getPaymentFormInputs($cart))
        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.png'));

    return [$externalOption];
  }

  /**
   * @return string
   */
  public function getContent() {
    $output = '';

    if (Tools::isSubmit('submit' . $this->name)) {
      $output = $this->saveConfigurationSettings();
    }

    return $output . $this->buildConfigurationForm();
  }

  /**
   * @param Cart $cart
   *
   * @return bool
   */
  private function checkCurrency(Cart $cart) {
    $currency_order    = new Currency($cart->id_currency);
    $currencies_module = $this->getCurrency($cart->id_currency);

    if (is_array($currencies_module)) {
      foreach ($currencies_module as $currency_module) {
        if ($currency_order->id == $currency_module['id_currency']) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * @return string
   */
  private function saveConfigurationSettings() {
    $output    = '';
    $hasErrors = false;

    foreach (static::$configuration as $name => $key) {
      $value = strval(Tools::getValue($key));
      if (!$value
          || empty($value)
          || !Validate::isGenericName($value)
      ) {
        $output    .= $this->displayError($this->l('Invalid value for ') . $this->l($name));
        $hasErrors = true;
      } else {
        Configuration::updateValue($key, $value);
      }
    }

    if (!$hasErrors) {
      $output = $this->displayConfirmation($this->l('Settings successfully updated.'));
    }

    return $output;
  }

  /**
   * @return string
   */
  private function buildConfigurationForm() {
    // Init Fields form array
    $fields_form[0]['form'] = [
        'legend' => [
            'title' => $this->l('Settings'),
        ],
        'input'  => $this->getConfigurationInputs(),
        'submit' => [
            'title' => $this->l('Save'),
            'class' => 'btn btn-default pull-right'
        ]
    ];

    $helper = $this->createHelper();

    // Load current values
    foreach (self::$configuration as $value) {
      $helper->fields_value[$value] = Configuration::get($value);
    }

    return $helper->generateForm($fields_form);
  }

  /**
   * @return array
   */
  private function getConfigurationInputs() {
    $inputs = [];

    foreach (static::$configuration as $name => $key) {
      $inputs[] = [
          'type'     => 'text',
          'label'    => $this->l($name),
          'name'     => $key,
          'size'     => 20,
          'required' => true
      ];
    }

    return $inputs;
  }

  /**
   * @return HelperForm
   */
  private function createHelper() {
    $helper       = new HelperForm();
    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Module, token and currentIndex
    $helper->module          = $this;
    $helper->name_controller = $this->name;
    $helper->token           = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

    // Language
    $helper->default_form_language    = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

    // Title and toolbar
    $helper->title          = $this->displayName;
    $helper->show_toolbar   = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action  = 'submit' . $this->name;
    $helper->toolbar_btn    = [
        'save' =>
            [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                          '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
        'back' => [
            'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        ]
    ];

    return $helper;
  }

  /**
   * @param Cart $cart
   *
   * @return array
   */
  private function getPaymentFormInputs(Cart $cart) {
    $inputs   = [];
    $key      = Configuration::get(self::CONFIGURATION_KEY);
    $errorUrl = $this->context->shop->getBaseURL() . 'index.php?controller=order&step=1';

    $inputs[self::FIELD_KEY]       = $this->createHiddenInput(self::FIELD_KEY, $key);
    $inputs[self::FIELD_ORDER]     = $this->createHiddenInput(self::FIELD_ORDER, $cart->id);
    $inputs[self::FIELD_URL]       = $this->createHiddenInput(self::FIELD_URL, $this->createConfirmationUrl($cart));
    $inputs[self::FIELD_ERROR_URL] = $this->createHiddenInput(self::FIELD_ERROR_URL, $errorUrl);
    $inputs[self::FIELD_DATA]      = $this->createHiddenInput(self::FIELD_DATA, $this->getEncodedData($cart));
    $inputs[self::FIELD_SIGN]      = $this->createHiddenInput(self::FIELD_SIGN, $this->calculateSign($inputs));

    return $inputs;
  }

  /**
   * @param string $name
   * @param string $value
   *
   * @return array
   */
  private function createHiddenInput($name, $value) {
    return [
        'name'  => $name,
        'type'  => 'hidden',
        'value' => $value,
    ];
  }

  /**
   * @param Cart $cart
   *
   * @return string
   */
  private function createConfirmationUrl(Cart $cart) {
    $customer = new Customer($cart->id_customer);

    return $this->context->shop->getBaseURL()
           . 'index.php?controller=order-confirmation&id_cart=' . $cart->id
           . '&id_module=' . $this->id
           . '&key=' . $customer->secure_key;
  }

  /**
   * @param Cart $cart
   *
   * @return string
   */
  private function getEncodedData(Cart $cart) {
    $currency = new Currency($cart->id_currency);

    return base64_encode(
        json_encode(
            [
                'amount'   => sprintf("%01.2f", $cart->getOrderTotal()),
                'name'     => 'Order from ' . $this->context->shop->name,
                'currency' => $currency->iso_code
            ]
        )
    );
  }

  /**
   * @param $inputs
   *
   * @return string
   */
  private function calculateSign($inputs) {
    $sign = md5(
        strtoupper(
            strrev($inputs[self::FIELD_KEY][self::KEY_VALUE]) .
            strrev($inputs[self::FIELD_DATA][self::KEY_VALUE]) .
            strrev($inputs[self::FIELD_URL][self::KEY_VALUE]) .
            strrev(
                Configuration::get(self::CONFIGURATION_PASSWORD)
            )
        )
    );

    return $sign;
  }

  /**
   * @return bool
   */
  private function deleteConfiguration() {
    $result = true;
    foreach (self::$configuration as $value) {
      $result &= Configuration::deleteByName($value);
    }

    return (bool)$result;
  }
}
