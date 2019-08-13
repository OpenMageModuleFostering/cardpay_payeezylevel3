<?php
/**
 * Cardpay Solutions, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php.
 *
 * PHP version 5
 * 
 * @category  Cardpay
 * @package   Cardpay_PayeezyLevel3
 * @copyright Copyright (c) 2015 Cardpay Solutions, Inc. (http://www.cardpaysolutions.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
 
/**
 * First Data PayeezyLevel3 info block.
 *
 * @category Cardpay
 * @package  Cardpay_PayeezyLevel3
 * @author   Cardpay Solutions, Inc. <sales@cardpaysolutions.com>
 */
class Cardpay_PayeezyLevel3_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Return credit card type
     * 
     * @return string card type name
     */
    public function getCcTypeName()
    {
        $types = Mage::getSingleton('payment/config')->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        }
        return (empty($ccType)) ? Mage::helper('payeezylevel3')->__('Stored Card') : $ccType;
    }

    /**
     * If has expiration date
     * 
     * @return bool if has expiration date
     */
    public function hasCcExpDate()
    {
        return (int)$this->getInfo()->getCcExpMonth() || (int)$this->getInfo()->getCcExpYear();
    }

    /**
     * Return credit card expiration
     * 
     * @return string formatted expiration date
     */
    public function getCcExpDate()
    {
        $month = $this->getInfo()->getCcExpMonth();
        $year = $this->getInfo()->getCcExpYear();
        return sprintf('%s/%s', sprintf('%02d', $month), $year);
    }

    /**
     * Prepare information specific to current payment method
     * 
     * @param null | array $transport
     * 
     * @return Varien_Object specific information
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        if ($ccType = $this->getCcTypeName()) {
            $data[Mage::helper('payeezylevel3')->__('Credit Card Type')] = $ccType;
        }
        if ($ccLast = $this->getInfo()->getCcLast4()) {
            $data[Mage::helper('payeezylevel3')->__('Credit Card Number')] = sprintf('xxxx-%s', $ccLast);
        }
        if ($this->hasCcExpDate()) {
            $data[Mage::helper('payeezylevel3')->__('Expiration Date')] = $this->getCcExpDate();
        }
        if (Mage::app()->getStore()->isAdmin()) {
            if ($this->getInfo()->getCcAvsStatus()) {
                $data[Mage::helper('payeezylevel3')->__('AVS Response')] = $this->getInfo()->getCcAvsStatus();
            }
            if ($this->getInfo()->getCcCidStatus()) {
                $data[Mage::helper('payeezylevel3')->__('CVV2 Response')] = $this->getInfo()->getCcCidStatus();
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}