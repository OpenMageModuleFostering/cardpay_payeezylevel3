<?php
/**
 * Cardpay Solutions, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 * 
 * @category  Cardpay
 * @package   Cardpay_PayeezyLevel3
 * @copyright Copyright (c) 2015 Cardpay Solutions, Inc. (http://www.cardpaysolutions.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
 
/**
 * First Data PayeezyLevel3 payment method model.
 *
 * @category Cardpay
 * @package  Cardpay_PayeezyLevel3
 * @author   Cardpay Solutions, Inc. <sales@cardpaysolutions.com>
 */
class Cardpay_PayeezyLevel3_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
{
    protected $_code = 'payeezylevel3';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_canRefundInvoicePartial = true;

    protected $_formBlockType = 'payeezylevel3/form';
    protected $_infoBlockType = 'payeezylevel3/info';

    private $_baseURL;
    private $_url;
    private $_merchantToken;

    const API_KEY = 'tF92QqXikGklcppFBAfzZnDFghkjBXrN';
    const API_SECRET = 'ab66d97bab5a4781c50fff107a1eb10e0bb40258385fb7a4da2fa5b320d128ec';

    /**
     * Validate data
     * 
     * @return bool true
     */
    public function validate()
    {
        return true;
    }

    /**
     * Authorizes specified amount
     * 
     * @param Varien_Object $payment payment object
     * @param decimal       $amount  amount in decimals
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $post = Mage::app()->getRequest()->getPost();
        $payload = $this->getPayload($payment, $amount, "authorize");
        $headerArray = $this->hmacAuthorizationToken($payload);
        $response = json_decode($this->postTransaction($payload, $headerArray));
        if ($response->transaction_status == "approved") {
            $payment->setTransactionId($response->transaction_id)
                ->setCcApproval($response->transaction_tag)
                ->setCcTransId($response->transaction_id)
                ->setIsTransactionClosed(0)
                ->setParentTransactionId(null)
                ->setCcAvsStatus(Mage::helper('payeezylevel3')->getAvsResponse($response->avs))
                ->setCcCidStatus(Mage::helper('payeezylevel3')->getCvvResponse($response->cvv2));
            if (isset($post['payment']['save_card'])) {
                $this->saveCard($payment, $response->token->token_data->value);
            }
            return $this;
        } else {
            if ($response->bank_message) {
                Mage::throwException('Transaction Declined: ' . $response->bank_message);
            } else {
                if ($response->Error->messages[0]->description == 'Access denied') {
                    Mage::throwException('Invalid Merchant Token: Call merchant support at (866) 588-0503 to obtain a new token');
                } else {
                    Mage::throwException('Transaction Error: ' . $response->Error->messages[0]->description);
                }
                
            }
            
        }
    }

    /**
     * Captures specified amount
     * 
     * @param Varien_Object $payment payment object
     * @param decimal       $amount  amount in decimals
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($payment->getParentTransactionId()) {
            $payload = $this->getPayload($payment, $amount, "capture");
            $headerArray = $this->hmacAuthorizationToken($payload);
            $response = json_decode($this->postTransaction($payload, $headerArray));
            if ($response->transaction_status == "approved") {
                $payment->setTransactionId($response->transaction_id)
                    ->setCcApproval($response->transaction_tag)
                    ->setCcTransId($response->transaction_id)
                    ->setIsTransactionClosed(1)
                    ->setParentTransactionId($payment->getParentTransactionId());
                return $this;
            } else {
                if ($response->bank_message) {
                    Mage::throwException('Capture Failed: ' . $response->bank_message);
                } else {
                    Mage::throwException('Capture Error: ' . $response->Error->messages[0]->description);
                }
            }
        } else {
            return $this->purchase($payment, $amount);
        }
    }

    /**
     * Authoirzes and captures specified amount
     * 
     * @param Varien_Object $payment payment object
     * @param decimal       $amount  amount in decimals
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function purchase(Varien_Object $payment, $amount)
    {
        $post = Mage::app()->getRequest()->getPost();
        $payload = $this->getPayload($payment, $amount, "purchase");
        $headerArray = $this->hmacAuthorizationToken($payload);
        $response = json_decode($this->postTransaction($payload, $headerArray));
        if ($response->transaction_status == "approved") {
            $payment->setTransactionId($response->transaction_id)
                ->setCcApproval($response->transaction_tag)
                ->setCcTransId($response->transaction_id)
                ->setIsTransactionClosed(1)
                ->setParentTransactionId(null)
                ->setCcAvsStatus(Mage::helper('payeezylevel3')->getAvsResponse($response->avs))
                ->setCcCidStatus(Mage::helper('payeezylevel3')->getCvvResponse($response->cvv2));
            if (isset($post['payment']['save_card'])) {
                $this->saveCard($payment, $response->token->token_data->value);
            }
            return $this;
        } else {
            if ($response->bank_message) {
                Mage::throwException('Transaction Declined: ' . $response->bank_message);
            } else {
                if ($response->Error->messages[0]->description == 'Access denied') {
                    Mage::throwException('Invalid Merchant Token: Call merchant support at (866) 588-0503 to obtain a new token');
                } else {
                    Mage::throwException('Transaction Error: ' . $response->Error->messages[0]->description);
                }
            }
        }
    }

    /**
     * Refunds specified amount
     * 
     * @param Varien_Object $payment payment object
     * @param decimal       $amount  amount in decimals
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if ($payment->getParentTransactionId()) {
            $payload = $this->getPayload($payment, $amount, "refund");
            $headerArray = $this->hmacAuthorizationToken($payload);
            $response = json_decode($this->postTransaction($payload, $headerArray));
            if ($response->transaction_status == "approved") {
                $payment->setTransactionId($response->transaction_id)
                    ->setCcApproval($response->transaction_tag)
                    ->setCcTransId($response->transaction_id)
                    ->setIsTransactionClosed(1)
                    ->setParentTransactionId($payment->getParentTransactionId());
                return $this;
            } else {
                if ($response->bank_message) {
                    Mage::throwException('Refund Failed: ' . $response->bank_message);
                } else {
                    Mage::throwException('Refund Error: ' . $response->Error->messages[0]->description);
                }
            }
        } else {
            Mage::throwException('Refund Failed: Invalid parent transaction ID.');
        }
    }

    /**
     * Voides authorized transaction
     * 
     * @param Varien_Object $payment payment object
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function void(Varien_Object $payment)
    {
        if ($payment->getParentTransactionId()) {
            $amount = $payment->getBaseAmountAuthorized();
            $payload = $this->getPayload($payment, $amount, "void");
            $headerArray = $this->hmacAuthorizationToken($payload);
            $response = json_decode($this->postTransaction($payload, $headerArray));
            if ($response->transaction_status == "approved") {
                $payment->setTransactionId($response->transaction_id)
                    ->setCcApproval($response->transaction_tag)
                    ->setCcTransId($response->transaction_id)
                    ->setIsTransactionClosed(1)
                    ->setParentTransactionId($payment->getParentTransactionId());
                return $this;
            } else {
                if ($response->bank_message) {
                    Mage::throwException('Void Failed: ' . $response->bank_message);
                } else {
                    Mage::throwException('Void Error: ' . $response->Error->messages[0]->description);
                }
            }
        } else {
            Mage::throwException('Void Failed: Invalid parent transaction ID.');
        }
    }

    /**
     * Voides transaction on cancel action
     * 
     * @param Varien_Object $payment payment object
     * 
     * @return Cardpay_PayeezyLevel3_Model_PaymentMethod payment method object
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }
    
    /**
     * Requests token for card
     * 
     * @param Cardpay_PayeezyLevel3_Model_Creditcard $card credit card object
     * 
     * @return string token value
     */
    public function verify($card)
    {
        $payload = $this->getTokenPayload($card);
        $headerArray = $this->hmacAuthorizationToken($payload);
        $response = json_decode($this->postTransaction($payload, $headerArray));
        if ($response->status == "success") {
            return $response->token->value;
        } else {
            if ($response->status) {
                Mage::throwException('Card Declined');
            } else {
                Mage::throwException($response->Error->messages[0]->description);
            }
        }
    }

    /**
     * Saves card and transarmor token
     * 
     * @param Varien_Object $payment payment object
     * @param string        $token   token value
     * 
     * @return Cardpay_PayeezyLevel3_Model_Creditcard credit card object
     */
    public function saveCard(Varien_Object $payment, $token)
    {
        if ($token) {
            $customerId = $payment->getOrder()->getCustomerId();
            $card = Mage::getModel('payeezylevel3/creditcard');
            $card->setData('customer_id', $customerId);
            $card->setData('token', $token);
            $card->setData('cc_exp_month', $payment->getCcExpMonth());
            $card->setData('cc_exp_year', $payment->getCcExpYear());
            $card->setData('cc_type', $payment->getCcType());
            if (count($card->currentCustomerCards()) || count($card->adminCustomerCards())) {
                $card->setData('is_default', '0');
            } else {
                $card->setData('is_default', '1');
            }
            $card->save();
        }
    }

    /**
     * Returns a previously saved card
     * 
     * @param string $token token value
     * 
     * @return Cardpay_PayeezyLevel3_Model_Creditcard credit card object
     */
    public function getSavedCard($token)
    {
        $card = Mage::getModel('payeezylevel3/creditcard')->load($token);
        return $card;
    }

    /**
     * Returns payload for transaction
     * 
     * @param Varien_Object $payment          payment object
     * @param decimal       $amount           amount in decimals
     * @param string        $transaction_type transaction type string
     * 
     * @return array payload
     */
    public function getPayload(Varien_Object $payment, $amount, $transactionType)
    {
        $post = Mage::app()->getRequest()->getPost();
        if (isset($post['payment']['token']) && !empty($post['payment']['token'])) {
            $card = $this->getSavedCard($post['payment']['token']);
            $payment->setCcExpYear($card->getCcExpYear())
                ->setCcExpMonth($card->getCcExpMonth())
                ->setCcType($card->getCcType())
                ->setCcLast4(substr($card->getToken(), -4));
        }
        $order = $payment->getOrder();
        $orderId = $order->getIncrementId();
        $billing = $order->getBillingAddress();
        $amountInCents = $amount * 100;
        $yr = substr($payment->getCcExpYear(), -2);
        $expDate = sprintf('%02d%02d', $payment->getCcExpMonth(), $yr);
        $testMode = $this->getConfigData('test_mode');
        $currency = $this->getConfigData('currency');
        $ccType = Mage::helper('payeezylevel3')->getCcTypeName($payment->getCcType());
        $data = '';

        if ($testMode) {
            $this->_baseURL = 'https://api-cert.payeezy.com/v1/transactions';
            $this->_merchantToken = 'fdoa-a480ce8951daa73262734cf102641994c1e55e7cdf4c02b6';
        } else {
            $this->_baseURL = 'https://api.payeezy.com/v1/transactions';
            $this->_merchantToken = $this->getConfigData('merchant_token');
        }

        if ($transactionType == "authorize" || $transactionType == "purchase") {
            if (isset($card)) {
                $data = array(
                    'merchant_ref' => $this->processInput($orderId),
                    'transaction_type' => $this->processInput($transactionType),
                    'method' => 'token',
                    'amount' => $this->processInput($amountInCents),
                    'currency_code' => $this->processInput($currency),
                    'token' => array(
                        'token_type' => 'FDToken',
                        'token_data' => array(
                            'type' => $this->processInput($ccType),
                            'value' => $this->processInput($card->getToken()),
                            'cardholder_name' => $this->processInput($billing->getName()),
                            'exp_date' => $this->processInput($expDate)
                        )
                    ),
                    'billing_address' => array(
                        'street' => $this->processInput(substr($billing->getStreet(1), 0, 30)),
                        'zip_postal_code' => $this->processInput(substr($billing->getPostcode(), 0, 10))
                    ),
                    'level2' => array(
                        'tax1_amount' => number_format($order->getTaxAmount(), '2', '.', ''),
                        'customer_ref' => $this->processInput($orderId)
                    ),
                    'level3' => $this->getLevel3Data($payment)
                );
            } else {
                $data = array(
                    'merchant_ref' => $this->processInput($orderId),
                    'transaction_type' => $this->processInput($transactionType),
                    'method' => 'credit_card',
                    'amount' => $this->processInput($amountInCents),
                    'currency_code' => $this->processInput($currency),
                    'credit_card' => array(
                        'type' => $this->processInput(Mage::helper('payeezylevel3')->getCcTypeName($payment->getCcType())),
                        'cardholder_name' => $this->processInput($billing->getName()),
                        'card_number' => $this->processInput($payment->getCcNumber()),
                        'exp_date' => $this->processInput($expDate),
                        'cvv' => $this->processInput($payment->getCcCid())
                    ),
                    'billing_address' => array(
                        'street' => $this->processInput(substr($billing->getStreet(1), 0, 30)),
                        'zip_postal_code' => $this->processInput(substr($billing->getPostcode(), 0, 10))
                    ),
                    'level2' => array(
                        'tax1_amount' => number_format($order->getTaxAmount(), '2', '.', ''),
                        'customer_ref' => $this->processInput($orderId)
                    ),
                    'level3' => $this->getLevel3Data($payment)
                );
            }
            $this->_url = $this->_baseURL;
        } else {
            $this->_url = $this->_baseURL . '/' . $payment->getParentTransactionId();
            $data = array(
                'merchant_ref' => $this->processInput($orderId),
                'transaction_type' => $this->processInput($transactionType),
                'method' => $this->processInput('credit_card'),
                'amount' => $this->processInput($amountInCents),
                'currency_code' => $this->processInput($currency),
                'transaction_tag' => $this->processInput($payment->getCcApproval())
            );
        }
        return json_encode($data);
    }

    /**
     * Returns Level 3 payment data for payload
     * 
     * @param Varien_Object $payment payment object
     * 
     * @return array level3
     */
    public function getLevel3Data(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        $order_items = $order->getAllVisibleItems();
        $shipping_address = $order->getShippingAddress();
        $shipping_phone = preg_replace('/[^0-9]/','', $shipping_address->getTelephone());
        $commodity_code = $this->getConfigData('commodity_code');
        $ship_from_zip = Mage::getStoreConfig('shipping/origin/postcode');
        $line_items = array();
        foreach ($order_items as $item) {
            $line_items[] = array(
                'description' => $this->processInput(substr($item->getName(), 0, 26)),
                'quantity' => (int)$item->getQtyOrdered(),
                'commodity_code' => $this->processInput(substr($commodity_code, 0, 12)),
                'discount_amount' => number_format($item->getDiscountAmount(), '2', '.', ''),
                'discount_indicator' => $item->getDiscountAmount() > 0 ? '1' : '0',
                'gross_net_indicator' => '1',
                'line_item_total' => number_format($item->getRowTotal(), '2', '.', ''),
                'product_code' => $this->processInput(substr($item->getSku(), 0, 12)),
                'tax_amount' => number_format($item->getTaxAmount(), '2', '.', ''),
                'tax_rate' => number_format($item->getTaxPercent(), '2', '.', ''),
                'tax_type' => '2',
                'unit_cost' => number_format($item->getPrice(), '2', '.', ''),
                'unit_of_measure' => 'EA'
            );
        }

        $level3 = array(
            'discount_amount' => number_format(abs($order->getDiscountAmount()), '2', '.', ''),
            'duty_amount' => '0.00',
            'freight_amount' => number_format($order->getShippingAmount(), '2', '.', ''),
            'ship_from_zip' => $this->processInput(substr($ship_from_zip, 0, 10)),
            'ship_to_address' => array(
                'address_1' => $this->processInput(substr($shipping_address->getStreetFull(), 0, 28)),
                'city' => $this->processInput(substr($shipping_address->getCity(), 0, 20)),
                'state' => $this->processInput(substr($shipping_address->getRegionCode(), 0, 2)),
                'zip' => $this->processInput(substr($shipping_address->getPostcode(), 0, 10)),
                'country' => $this->processInput($shipping_address->getCountry()),
                'customer_number' => $this->processInput($order->getIncrementId()),
                'email' => $this->processInput(substr($order->getCustomerEmail(), 0, 50)),
                'name' => $this->processInput(substr($shipping_address->getName(), 0, 28)),
                'phone' => $this->processInput(substr($shipping_phone, 0, 14))
            ),
            'line_items' => $line_items
        );
        return $level3;
    }

    /**
     * Returns payload for token request
     * 
     * @param Cardpay_PayeezyLevel3_Model_Creditcard $card credit card object
     * 
     * @return array payload
     */
    public function getTokenPayload($card)
    {
        $yr = substr($card->getCcExpYear(), -2);
        $expDate = sprintf('%02d%02d', $card->getCcExpMonth(), $yr);
        $testMode = $this->getConfigData('test_mode');
        $data = '';

        if ($testMode) {
            $this->_baseURL = 'https://api-cert.payeezy.com/v1/transactions';
            $this->_merchantToken = 'fdoa-a480ce8951daa73262734cf102641994c1e55e7cdf4c02b6';
            $taToken = 'NOIW';
        } else {
            $this->_baseURL = 'https://api.payeezy.com/v1/transactions';
            $this->_merchantToken = $this->getConfigData('merchant_token');
            $taToken = $this->getConfigData('ta_token');
        }

        $data = array(
            'type' => 'FDToken',
            'auth' => 'false',
            'ta_token' => $this->processInput($taToken),
            'credit_card' => array(
                'type' => $this->processInput(Mage::helper('payeezylevel3')->getCcTypeName($card->getCcType())),
                'cardholder_name' => $this->processInput($card->getCardholderName()),
                'card_number' => $this->processInput($card->getCcNumber()),
                'exp_date' => $this->processInput($expDate),
                'cvv' => $this->processInput($card->getCcCid())
            )
        );
        $this->_url = $this->_baseURL . '/tokens';
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    /**
     * Returns HMAC authorization values
     * 
     * @param array $payload payload
     * 
     * @return array hmac values
     */
    public function hmacAuthorizationToken($payload)
    {
        $nonce         = strval(hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstrong))));
        $timestamp     = strval(time() * 1000); //time stamp in milli seconds
        $data          = self::API_KEY . $nonce . $timestamp . $this->_merchantToken . $payload;
        $hashAlgorithm = "sha256";
        $hmac          = hash_hmac($hashAlgorithm, $data, self::API_SECRET, false); // HMAC Hash in hex
        $authorization = base64_encode($hmac);
        return array(
            'authorization' => $authorization,
            'nonce' => $nonce,
            'timestamp' => $timestamp
        );
    }

    /**
     * Post transaction to gateway
     * 
     * @param array $payload payload
     * @param array $headers headers
     * 
     * @return string json response
     */
    public function postTransaction($payload, $headers)
    {
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $this->_url);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HEADER, false);
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $request, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'apikey:' . strval(self::API_KEY),
                'token:' . strval($this->_merchantToken),
                'Authorization:' . $headers['authorization'],
                'nonce:' . $headers['nonce'],
                'timestamp:' . $headers['timestamp']
            )
        );
        $response = curl_exec($request);
        if (false === $response) {
            Mage::throwException('Transaction Error: ' . curl_error($request));
        }
        curl_close($request);
        return $response;
    }

    /**
     * Returns processed input
     * 
     * @param string $data input data
     * 
     * @return string processed input
     */
    public function processInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return strval($data);
    }
    
    /**
     * If payment method is available for currency
     * 
     * @param string $currencyCode order currency
     * 
     * @return bool available for currency or not
     */
    public function canUseForCurrency($currencyCode)
    {
        if ($currencyCode != $this->getConfigData('currency')) {
            return false;
        }
        return true;
    }
}