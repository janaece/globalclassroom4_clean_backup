<?php
/*
 * This file is part of the prestaPaypalPlugin package.
 * (c) Matthieu CRINQUAND <mcrinquand@prestaconcept.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package PayPal
 */

/**
 * Make sure our parent class is defined.
 */
require_once 'PayPal/Type/AbstractRequestType.php';

/**
 * SetCustomerBillingAgreementRequestType
 *
 * @package PayPal
 */
class SetCustomerBillingAgreementRequestType extends AbstractRequestType
{
    var $SetCustomerBillingAgreementRequestDetails;

    function SetCustomerBillingAgreementRequestType()
    {
        parent::AbstractRequestType();
        $this->_namespace = 'urn:ebay:api:PayPalAPI';
        $this->_elements = array_merge($this->_elements,
            array (
              'SetCustomerBillingAgreementRequestDetails' => 
              array (
                'required' => true,
                'type' => 'SetCustomerBillingAgreementRequestDetailsType',
                'namespace' => 'urn:ebay:apis:eBLBaseComponents',
              ),
            ));
    }

    function getSetCustomerBillingAgreementRequestDetails()
    {
        return $this->SetCustomerBillingAgreementRequestDetails;
    }
    function setSetCustomerBillingAgreementRequestDetails($SetCustomerBillingAgreementRequestDetails, $charset = 'iso-8859-1')
    {
        $this->SetCustomerBillingAgreementRequestDetails = $SetCustomerBillingAgreementRequestDetails;
        $this->_elements['SetCustomerBillingAgreementRequestDetails']['charset'] = $charset;
    }
}
