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
 * First Data PayeezyLevel3 data helper.
 *
 * @category Cardpay
 * @package  Cardpay_PayeezyLevel3
 * @author   Cardpay Solutions, Inc. <sales@cardpaysolutions.com>
 */
class Cardpay_PayeezyLevel3_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns credit card name
     * 
     * @param $code credit card type code
     * 
     * @return string card type name
     */
    public function getCcTypeName($code)
    {
        $ccTypes = array(
            'VI' => 'Visa',
            'MC' => 'MasterCard',
            'AE' => 'American Express',
            'DI' => 'Discover',
            'JCB' => 'JCB'
        );
        return $ccTypes[$code];
    }
    
    /**
     * Returns avs response description
     * 
     * @param $code avs response code
     * 
     * @return string avs message
     */
    public function getAvsResponse($code)
    {
        $avsResponses = array(
            'X' => 'Exact match, 9 digit zip - Street Address, and 9 digit ZIP Code match',
            'Y' => 'Exact match, 5 digit zip - Street Address, and 5 digit ZIP Code match',
            'A' => 'Partial match - Street Address matches, ZIP Code does not',
            'W' => 'Partial match - ZIP Code matches, Street Address does not',
            'Z' => 'Partial match - 5 digit ZIP Code match only',
            'N' => 'No match - No Address or ZIP Code match',
            'U' => 'Unavailable - Address information is unavailable for that account number, 
                or the card issuer does not support',
            'G' => 'Service Not supported, non-US Issuer does not participate',
            'R' => 'Retry - Issuer system unavailable, retry later',
            'E' => 'Not a mail or phone order',
            'S' => 'Service not supported',
            'Q' => 'Bill to address did not pass edit checks',
            'D' => 'International street address and postal code match',
            'B' => 'International street address match',
            'C' => 'International street address and postal code not verified due to incompatable formats',
            'P' => 'International postal code match, street address not verified due to incompatable format',
            '1' => 'Cardholder name matches',
            '2' => 'Cardholder name, billing address, and postal code match',
            '3' => 'Cardholder name and billing postal code match',
            '4' => 'Cardholder name and billing address match',
            '5' => 'Cardholder name incorrect, billing address and postal code match',
            '6' => 'Cardholder name incorrect, billing postal code matches',
            '7' => 'Cardholder name incorrect, billing address matches',
            '8' => 'Cardholder name, billing address, and postal code are all incorrect'
        );
        if (array_key_exists($code, $avsResponses)) {
            return $avsResponses[$code];
        } else {
            return '';
        }
    }

    /**
     * Returns cvv response description
     * 
     * @param $code cvv response code
     * 
     * @return string cvv message
     */
    public function getCvvResponse($code)
    {
        $cvvResponses = array(
            'M' => 'CVV2/CVC2 Match',
            'N' => 'CVV2 / CVC2 No Match',
            'P' => 'Not Processed',
            'S' => 'Merchant Has Indicated that CVV2 / CVC2 is not present on card',
            'U' => 'Issuer is not certified and/or has not provided visa encryption keys',
            'I' => 'CVV2 code is invalid or empty'
        );
        if (array_key_exists($code, $cvvResponses)) {
            return $cvvResponses[$code];
        } else {
            return '';
        }
    }
}