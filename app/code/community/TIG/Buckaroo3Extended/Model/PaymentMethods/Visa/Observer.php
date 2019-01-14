<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Visa_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_visa';
    protected $_method = 'visa';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array(
            $this->_method     => array(
                'action'    => 'Pay',
                'version'   => 1,
            ),
        );

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' .  $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $array['creditmanagement'] = array(
                    'action'    => 'Invoice',
                    'version'   => 1,
            );
        }

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    public function buckaroo3extended_refund_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    public function buckaroo3extended_refund_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $refundRequest = $observer->getRequest();

        $vars = $refundRequest->getVars();

        $array = array(
            'action'    => 'Refund',
            'version'   => 1,
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'][$this->_method])) {
            $vars['services'][$this->_method] = array_merge($vars['services'][$this->_method], $array);
        } else {
            $vars['services'][$this->_method] = $array;
        }

        $refundRequest->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        return $this;
    }

    public function buckaroo3extended_return_custom_processing(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $response = $observer->getPostArray();
        $order = $observer->getOrder();

        $enrolled = false;
        $authenticated = false;
        if (isset($response['brq_SERVICE_visa_Enrolled']) && isset($response['brq_SERVICE_visa_Authentication'])) {
            $enrolled = $response['brq_SERVICE_visa_Enrolled'];
            $enrolled = ($enrolled == 'Y') ? true : false;

            /**
             * The status selected below determines how the payment or authorize is processed.
             * A,Y,N,U :
             * Attempt/ Yes will lead to a successfull transaction/payment while No/ Unknown will leade to a failure.
             */
            $authenticated = $response['brq_SERVICE_visa_Authentication'];
            $authenticated = ($authenticated == 'Y' || $authenticated == 'A') ? true : false;
        }

        $order->setBuckarooSecureEnrolled($enrolled)
              ->setBuckarooSecureAuthenticated($authenticated)
              ->save();

        if ($order->getTransactionKey()) {
            $this->_updateSecureStatus($enrolled, $authenticated, $order);
        }

        return $this;
    }

    public function buckaroo3extended_push_custom_processing_after(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $order = $observer->getOrder();
        $enrolled = $order->getBuckarooSecureEnrolled();
        $authenticated = $order->getBuckarooSecureAuthenticated();

        if (is_null($enrolled) || is_null($authenticated)) {
            return $this;
        }

        $this->_updateSecureStatus($enrolled, $authenticated, $order);

        return $this;
    }
}
