<?php


/**
 * Class PlatonCallbackModuleFrontController
 */
class PlatonCallbackModuleFrontController extends ModuleFrontController {
  public function postProcess() {
    $cardID        = Tools::getValue('order');
    $transactionID = Tools::getValue('id');
    $sign          = Tools::getValue('sign');
    $email         = Tools::getValue('email');
    $amount        = Tools::getValue('amount');
    $card          = Tools::getValue('card');
    $status        = Tools::getValue('status');
    $password      = Configuration::get('PLATON_PASSWORD');

    $cart = new Cart($cardID);
    if ($cart->id_customer == 0
        || $cart->id_address_delivery == 0
        || $cart->id_address_invoice == 0
        || !$this->module->active
    ) {
      die('Cannot create order for this cart.');
    }

    $customer = new Customer($cart->id_customer);
    if (!Validate::isLoadedObject($customer)) {
      die('No customer for this order.');
    }

    // generate signature from callback params
    $calculatedSign = md5(
        strtoupper(
            strrev($email) .
            $password .
            $cardID .
            strrev(substr($card, 0, 6) . substr($card, -4))
        )
    );
    if ($calculatedSign != $sign) {
      die('Wrong request signature.');
    }

    switch ($status) {
      case 'SALE':
        $currency = new Currency((int)$cart->id_currency);
        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PS_OS_PAYMENT'),
            $amount,
            $this->module->displayName,
            null,
            ['transaction_id' => $transactionID],
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        break;
    }

    // answer with success response
    exit("OK");
  }
}
