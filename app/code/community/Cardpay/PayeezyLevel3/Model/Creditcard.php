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
 * First Data PayeezyLevel3 credit card model.
 *
 * @category Cardpay
 * @package  Cardpay_PayeezyLevel3
 * @author   Cardpay Solutions, Inc. <sales@cardpaysolutions.com>
 */
class Cardpay_PayeezyLevel3_Model_Creditcard extends Mage_Core_Model_Abstract
{
    /**
     * Internal constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('payeezylevel3/creditcard');
    }

    /**
     * Returns current customer credit cards
     * 
     * @return array customer cards
     */
    public function currentCustomerCards()
    {
        if ($this->useVault() && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            $cards = $this->getCollection()->addFieldToFilter('customer_id', $customerId);
            return $cards;
        }
        return array();
    }

    /**
     * Returns current customer credit cards
     * 
     * @return array customer cards
     */
    public function adminCustomerCards()
    {
        if ($this->useVault()) {
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
            $cards = $this->getCollection()->addFieldToFilter('customer_id', $customerId);
            return $cards;
        }
        return array();
    }

    /**
     * If can use vault
     * 
     * @return bool if can use vault
     */
    public function useVault()
    {
        $payeezylevel3 = Mage::getModel('payeezylevel3/paymentmethod');
        return $payeezylevel3->getConfigData('use_vault');
    }

    /**
     * Returns credit card type name
     * 
     * @return string card type name
     */
    public function getCardTypeName()
    {
        $payeezylevel3 = Mage::getModel('payeezylevel3/paymentmethod');
        return Mage::helper('payeezylevel3')->getCcTypeName($this->getCcType());
    }

    /**
     * Returns masked credit card number
     * 
     * @return string masked card number
     */
    public function getMaskedCardNum()
    {
        return '************' . substr($this->getToken(), -4);
    }

    /**
     * Returns cardholder name
     * 
     * @return string cardholder name
     */
    public function getCardholderName()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $cardholderName = $customer->getName();
        return $cardholderName;
    }

    /**
     * Clears the current defualt credit card
     */
    public function clearDefault()
    {
        $cards = $this->currentCustomerCards();
        foreach ($cards as $card) {
            if ($card->getIsDefault()) {
                $card->setIsDefault('0');
                $card->save();
            }
        }
    }
}