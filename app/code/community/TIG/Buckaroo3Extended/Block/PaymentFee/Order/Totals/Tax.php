<?php
class TIG_Buckaroo3Extended_Block_PaymentFee_Order_Totals_Tax extends Mage_Adminhtml_Block_Sales_Order_Totals_Tax
{
    /**
     * Get full information about taxes applied to order
     *
     * @return array
     */
    public function getFullTaxInfo()
    {
        if(Mage::helper('buckaroo3extended')->getIsKlarnaEnabled()) {
            return parent::getFullTaxInfo();
        }
        
        /** @var $source Mage_Sales_Model_Order */
        $source = $this->getOrder();

        $taxClassAmount = array();
        if ($source instanceof Mage_Sales_Model_Order) {
            $taxClassAmount = Mage::helper('tax')->getCalculatedTaxes($source);
            if (empty($taxClassAmount)) {
                $rates = Mage::getModel('sales/order_tax')->getCollection()->loadByOrder($source)->toArray();
                $taxClassAmount =  Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);
            } else {
                $shippingTax    = Mage::helper('tax')->getShippingTax($source);
                if ($source->getBaseBuckarooFeeTax()) {
                    $buckarooFeeTax = array(
                        array(
                            'tax_amount'      => $source->getBuckarooFeeTax(),
                            'base_tax_amount' => $source->getBaseBuckarooFeeTax(),
                            'title'           => Mage::helper('buckaroo3extended')->__('Fee'),
                            'percent'         => NULL,
                        ),
                    );
                    $taxClassAmount = array_merge($shippingTax, $buckarooFeeTax, $taxClassAmount);
                } else {
                    $taxClassAmount = array_merge($shippingTax, $taxClassAmount);
                }
            }
        }

        return $taxClassAmount;
    }
}
