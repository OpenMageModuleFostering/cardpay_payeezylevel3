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
 * First Data PayeezyLevel3 credit card type source model.
 *
 * @category Cardpay
 * @package  Cardpay_PayeezyLevel3
 * @author   Cardpay Solutions, Inc. <sales@cardpaysolutions.com>
 */
class Cardpay_PayeezyLevel3_Model_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    /**
     * Allowed credit card types
     * 
     * @return array allowed card types
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB');
    }
}