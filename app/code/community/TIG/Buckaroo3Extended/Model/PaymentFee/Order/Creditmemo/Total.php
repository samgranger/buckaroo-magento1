<?php
class TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    /**
     * Retrieves Payment Fee values, calculates the amount that can be refunded
     * 
     * @param Mage_Sales_Model_Order_Creditmemo $invoice
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        //retreive all base fee-related values from order
        $baseBuckarooFee             = $order->getBaseBuckarooFeeInvoiced();
        $baseBuckarooFeeRefunded     = $order->getBaseBuckarooFeeRefunded();
        $baseBuckarooFeeTax          = $order->getBaseBuckarooFeeTaxInvoiced();
        $baseBuckarooFeeTaxRefunded  = $order->getBaseBuckarooFeeTaxRefunded();
        
        //retreive all fee-related values from order
        $buckarooFee                 = $order->getBuckarooFeeInvoiced();
        $buckarooFeeRefunded         = $order->getBuckarooFeeRefunded();
        $buckarooFeeTax              = $order->getBuckarooFeeTaxInvoiced();
        $buckarooFeeTaxRefunded      = $order->getBuckarooFeeTaxRefunded();
        
        //get current creditmemo totals
        $baseRefundTotal             = $creditmemo->getBaseGrandTotal();
        $creditmemoTotal             = $creditmemo->getGrandTotal();
        
        $baseTaxAmountTotal          = $creditmemo->getBaseTaxAmount();
        $taxAmountTotal              = $creditmemo->getTaxAmount();

        //calculate how much needs to be creditmemod
        $baseBuckarooFeeToRefund     = $baseBuckarooFee - $baseBuckarooFeeRefunded;
        $buckarooFeeToRefund         = $buckarooFee - $buckarooFeeRefunded;
        
        $baseBuckarooFeeTaxToRefund  = $baseBuckarooFeeTax - $baseBuckarooFeeTaxRefunded;
        $buckarooFeeTaxToRefund      = $buckarooFeeTax - $buckarooFeeTaxRefunded;
        
        $baseRefundTotal            += $baseBuckarooFeeToRefund;
        $creditmemoTotal            += $buckarooFeeToRefund;
        
        $baseTaxAmountTotal         += $baseBuckarooFeeTaxToRefund;
        $taxAmountTotal             += $buckarooFeeTaxToRefund;
        
        //set the new creditmemod values
        $creditmemo->setBaseGrandTotal($baseRefundTotal + $baseBuckarooFeeTaxToRefund);
        $creditmemo->setGrandTotal($creditmemoTotal + $buckarooFeeTaxToRefund);
        
        $creditmemo->setBaseTaxAmount($baseTaxAmountTotal);
        $creditmemo->setTaxAmount($taxAmountTotal);

        $creditmemo->setBaseBuckarooFee($baseBuckarooFeeToRefund);
        $creditmemo->setBuckarooFee($buckarooFeeToRefund);
        
        $creditmemo->setBaseBuckarooFeeTax($baseBuckarooFeeTax);
        $creditmemo->setBuckarooFeeTax($buckarooFeeTax);
        
        return $this;
    }
}