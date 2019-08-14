<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License
 *
 * Copyright (c) 2010 e-Boekhouden.nl
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Eboekhouden_Connector
 * @copyright  Copyright (c) 2010 e-Boekhouden.nl
 * @license    http://opensource.org/licenses/mit-license.php  The MIT License
 * @author     e-Boekhouden.nl
 */
class Eboekhouden_Connector_Model_Export_Sales { 
  
  /**
   * Export given orders to e-Boekhouden.nl.
   *
   * @param $aOrderIds List of order ids to export.
   * @return array with values ($iOrdersAdded,$iOrdersExist,$sErrorMsg)
   */
  public function exportOrders($aOrderIds) {
    $sErrorMsg = '';
    $iCountAdded = 0;
    $iCountExist = 0;           
    list($aSettings,$sConnErrorMsg) = $this->_getConnectorSettings();
    $sErrorMsg .= $sConnErrorMsg;

    sort($aOrderIds);
    if (empty($sErrorMsg)) {
      foreach ($aOrderIds as $sOrderId) {
        $oOrder = Mage::getModel('sales/order')->load($sOrderId); /* @var $oOrder Mage_Sales_Model_Order */
        $aSettings['sInvoiceNr'] = $oOrder->getRealOrderId();      
        $aSettings['sInvoiceDesc'] = Mage::helper('eboekhouden')->__('Magento order %s',$aSettings['sInvoiceNr']);
        list($iThisOrderMutatie,$iThisOrderExist,$sThisErrorMsg) = $this->_exportOrder($oOrder,$aSettings);
        $iCountAdded += (empty($iThisOrderMutatie)) ? 0 : 1;
        $iCountExist += $iThisOrderExist;
        $sErrorMsg .= $sThisErrorMsg;
      }
    }
    return array($iCountAdded,$iCountExist,$sErrorMsg);
  }

  /**
   * Export given invoices ids to e-Boekhouden.nl.
   *
   * @param $aInvoiceIds List of invoice ids to export.
   * @return array with values ($iOrdersAdded,$iOrdersExist,$sErrorMsg)
   */
  public function exportInvoices($aInvoiceIds) {
    $sErrorMsg = '';
    $iCountAdded = 0;
    $iCountExist = 0;
    list($aSettings,$sConnErrorMsg) = $this->_getConnectorSettings();
    $sErrorMsg .= $sConnErrorMsg;
                 
    sort($aInvoiceIds);
    if (empty($sErrorMsg)) {
      foreach ($aInvoiceIds as $sInvoiceId) {
        $oInvoice = Mage::getModel('sales/order_invoice')->load($sInvoiceId); /* @var $oInvoice Mage_Sales_Model_Order_Invoice */
        $oOrder = $oInvoice->getOrder();
        if ('O' == Mage::getStoreConfig('eboekhouden/settings/useasinvoicenr')) {           
          $aSettings['sInvoiceNr'] = $oOrder->getRealOrderId();      
          $aSettings['sInvoiceDesc'] = Mage::helper('eboekhouden')->__('Magento order %s',$aSettings['sInvoiceNr']);
        }
        else {
          $aSettings['sInvoiceNr'] = $oInvoice->getData('increment_id');
          $aSettings['sInvoiceDesc'] = Mage::helper('eboekhouden')->__('Magento factuur %s',$aSettings['sInvoiceNr']);
        }        
        list($iThisOrderMutatie,$iThisOrderExist,$sThisErrorMsg) = $this->_exportOrder($oOrder,$aSettings);
        if (!empty($iThisOrderMutatie)) {       
          $oInvoice->setData('eboekhouden_mutatie',$iThisOrderMutatie);
          $oInvoice->save();
        }        
        $iCountAdded += (empty($iThisOrderMutatie)) ? 0 : 1;
        $iCountExist += $iThisOrderExist;
        $sErrorMsg .= $sThisErrorMsg;
      }
    }
    return array($iCountAdded,$iCountExist,$sErrorMsg);
  }  
  
  protected function _getConnectorSettings() {
    $sErrorMsg = '';
    $aSettings = array();
    
    $aSettings['sConUser'] = trim(Mage::getStoreConfig('eboekhouden/connector/username'));
    $aSettings['sConWord'] = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode1'));
    $aSettings['sConGuid'] = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode2'));    
    
    if ( empty($aSettings['sConUser']) || empty($aSettings['sConWord']) || empty($aSettings['sConGuid']) ) {
      $sErrorMsg .= Mage::helper('eboekhouden')->__('Configuratie is niet volledig ingevuld, ga naar het menu "%s","%s" en kies "e-Boekhouden.nl" uit de zijbalk. Vul de gegevens in onder "Connector Login Gegevens"',Mage::helper('adminhtml')->__('System'),Mage::helper('adminhtml')->__('Configuration'));
    }
    
    $aSettings['aVatCodes'] = array();
    $aVatConfig = Mage::getStoreConfig('eboekhouden/vatcodes');
    foreach ($aVatConfig as $sConfigKey => $sVatMag) {
      $aMatch = array();
      if (preg_match('/vatcode_(\w+)$/',$sConfigKey,$aMatch)) {        
        $aSettings['aVatCodes'][$sVatMag] = $aMatch[1];
      }
    }
    
    return array($aSettings,$sErrorMsg);    
  }
  
  /**
   * export one Order
   * 
   * @param Mage_Sales_Model_Order $oOrder
   * @return array with values ($iOrdersAdded,$iOrdersExist,$sErrorMsg)
   */
  protected function _exportOrder($oOrder,$aSettings) {
    $iOrdersMutatie = 0;
    $iOrdersExist = 0;
    $sErrorMsg = '';
    
    $iOrderTime = strtotime($oOrder->getCreatedAt());    
    $oBillingAddress = $oOrder->getBillingAddress();     
    $sCompanyName = $oBillingAddress->getData("company");
    if (empty($sCompanyName)) {        
      $sCompanyName = $oBillingAddress->getName();
    }    
    
    $aVatPercToMagCode = array();                
    $aTaxInfo = $oOrder->getFullTaxInfo();
    foreach ($aTaxInfo as $aTaxRow) {
      foreach ($aTaxRow['rates'] as $aTaxRate) {
        if ( !empty($aTaxRate['code']) && isset($aTaxRate['percent']) ) {
          if (!empty($aSettings['aVatCodes'][$aTaxRate['code']])) {           
            $aVatPercToMagCode[$aTaxRate['percent']] = $aTaxRate['code']; 
          }
        }
      }
    }        
    
    $oClient = new Zend_Http_Client();
    $oClient->setUri('https://secure.e-boekhouden.nl/bh/api.asp');    
    
    $sXml = '<?xml version="1.0"?>'."\n";
    $sXml .= '
<API>
  <ACTION>ADD_MUTATIE</ACTION>
  <VERSION>1.0</VERSION>
  <SOURCE>Magento</SOURCE>
  <AUTH>
    <GEBRUIKERSNAAM>'.$aSettings['sConUser'].'</GEBRUIKERSNAAM>
    <WACHTWOORD>'.$aSettings['sConWord'].'</WACHTWOORD>
    <GUID>'.$aSettings['sConGuid'].'</GUID>
  </AUTH>
  <MUTATIE>
    <NAW>
      <BEDRIJF>'.$sCompanyName.'</BEDRIJF>
      <ADRES>'.$oBillingAddress->getData("street").'</ADRES>
      <POSTCODE>'.$oBillingAddress->getData("postcode").'</POSTCODE>
      <PLAATS>'.$oBillingAddress->getData("city").'</PLAATS>
      <LAND>'.$oBillingAddress->getCountryModel()->getName().'</LAND>
      <LANDCODE>'.$oBillingAddress->getCountry().'</LANDCODE>
      <TELEFOON>'.$oBillingAddress->getData("telephone").'</TELEFOON>
      <EMAIL>'.$oOrder->getCustomerEmail().'</EMAIL>
    </NAW>
    <SOORT>2</SOORT>
    <REKENING>1300</REKENING>
    <OMSCHRIJVING>'.$aSettings['sInvoiceDesc'].'</OMSCHRIJVING>
    <FACTUUR>'.$aSettings['sInvoiceNr'].'</FACTUUR>
    <BETALINGSTERMIJN>30</BETALINGSTERMIJN>
    <DATUM>'.date('d-m-Y',$iOrderTime).'</DATUM>
    <INEX>EX</INEX>
    <MUTATIEREGELS>';
            
    $aOrderItems = $oOrder->getItemsCollection();
    foreach ($aOrderItems as $oItem) /* @var $oItem Mage_Sales_Model_Order_Item */
    {
      if (!$oItem->isDummy()) 
      {
        $fPriceEx = $this->getOrderItemTotal($oItem) - $oItem->getTaxAmount();
        $iProductId = $oItem->getData('product_id');
        $oProduct = (empty($iProductId)) ? false : Mage::getModel('catalog/product')->setStore(0)->load($iProductId);
        $iGbRekening = 8000;
        if (!empty($oProduct)) {
          $iProductGbRekening = $oProduct->getData('eboekhouden_grootboekrekening');
          if (!empty($iProductGbRekening)) {
            $iGbRekening = $iProductGbRekening;
          }
        }
        
        $sVatCode = false;        
        $fVatPercent = floatval($oItem->getData('tax_percent'));       
        if (!empty($aVatPercToMagCode[$fVatPercent])) {
          $sMagCode = $aVatPercToMagCode[$fVatPercent];          
          if (!empty($aSettings['aVatCodes'][$sMagCode])) {
            $sVatCode = $aSettings['aVatCodes'][$sMagCode];
          }
        }        
        if (empty($sVatCode)) {
          if (0 == $fVatPercent) {
            $sVatCode = 'GEEN';
          }
          elseif (6 == $fVatPercent) {
            $sVatCode = 'LAAG_VERK';
          }
          else {
            $sVatCode = 'HOOG_VERK';
          }          
        }
        
        $sXml .= '
        <MUTATIEREGEL>
          <BEDRAGINCL>'.$this->getOrderItemTotal($oItem).'</BEDRAGINCL>
          <BEDRAGEXCL>'.$fPriceEx.'</BEDRAGEXCL>
          <BTWPERC>'.$sVatCode.'</BTWPERC>
          <BTWBEDRAG>'.$oItem->getTaxAmount().'</BTWBEDRAG>
          <TEGENREKENING>'.$iGbRekening.'</TEGENREKENING>
        </MUTATIEREGEL>';
      }
    }
        
    if (0 < $oOrder->getShippingAmount())
    {
      // Shipping & Handling cost
      $oCarrier = $oOrder->getShippingCarrier();
      $sXml .= '
        <MUTATIEREGEL>
          <BEDRAGINCL>'.($oOrder->getShippingAmount()+$oOrder->getShippingTaxAmount()).'</BEDRAGINCL>
          <BEDRAGEXCL>'.$oOrder->getShippingAmount().'</BEDRAGEXCL>
          <BTWPERC>HOOG_VERK</BTWPERC>
          <BTWBEDRAG>'.$oOrder->getShippingTaxAmount().'</BTWBEDRAG>
          <TEGENREKENING>8000</TEGENREKENING>
        </MUTATIEREGEL>';
    }
          
    if (0 < $oOrder->getDiscountAmount())
    {
      // Discount
      $sXml .= '
        <MUTATIEREGEL>
          <BEDRAGINCL>'.$oOrder->getDiscountAmount().'</BEDRAGINCL>
          <BEDRAGEXCL>'.$oOrder->getDiscountAmount().'</BEDRAGEXCL>
          <BTWPERC>GEEN</BTWPERC>
          <BTWBEDRAG>0</BTWBEDRAG>
          <TEGENREKENING>8000</TEGENREKENING>
        </MUTATIEREGEL>';
        }
        
    $sXml .= '
      </MUTATIEREGELS>
    </MUTATIE>
  </API>';
                                                
    $sXml = utf8_encode($sXml);
      
    // Enable for debugging:
    //$sErrorMsg .= str_replace('  ',' &nbsp;',htmlspecialchars($sXml));
      
    $oClient->setParameterPost('xml', $sXml);       
    $oResponse = $oClient->request('POST');
      
    if ($oResponse->isError()) {
      $sErrorMsg .= Mage::helper('eboekhouden')->__('HTTP fout %s ontvangen van API: %s',$oResponse->getStatus(),$oResponse->getMessage())."\n";
    }
    else {
      $sResponse = $oResponse->getBody();
      if (empty($sResponse)) {
        $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout: Leeg antwoord ontvangen van API')."\n";
      }
      else {
        $oData = @simplexml_load_string($sResponse);
        if (empty($oData)) {
          $sShowResponse = htmlspecialchars(strip_tags($sResponse));
          $sShowResponse = preg_replace('#\s*\n#',"\n",$sShowResponse);
          $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout in van API ontvangen XML: parsen mislukt')."\n".$sShowResponse."\n";            
        }
        elseif (empty($oData->RESULT)) {
          $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout in van API ontvangen XML: "RESULT" veld is leeg')."\n";
        }
        elseif ('ERROR' == $oData->RESULT) {
          if ('M006' == $oData->ERROR->CODE) {
            //$sErrorMsg .= 'Record bestaat al: '.$oData->ERROR->DESCRIPTION."\n";
            $iOrdersExist++;
          }
          else {
            $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout %s: %s',$oData->ERROR->CODE,$oData->ERROR->DESCRIPTION)."\n";
          }
        }
        elseif ( 'OK' == $oData->RESULT ) {
          // $sErrorMsg = 'OK, mutatie nummer: '.$oData->MUTNR."\n";
          $oOrder->setData('eboekhouden_mutatie',$oData->MUTNR);
          $oOrder->save();
          $iOrdersMutatie = $oData->MUTNR;
        }
        else {
          $sErrorMsg .= Mage::helper('eboekhouden')->__('Onbekend resultaat van API ontvangen: %s'.$oData->RESULT)."\n";
        }
      }
    }
    return array($iOrdersMutatie,$iOrdersExist,$sErrorMsg);    
  }    
  
  /**
   * Calculates and returns the total of an order item including tax and discount.
   *
   * @param Mage_Sales_Model_Order_Item $item The item to return info from
   * @return float The total
   */
  protected function getOrderItemTotal($oOrderItem) {
    $fResult = $oOrderItem->getRowTotal();
    $fResult -= $oOrderItem->getDiscountAmount();
    $fResult += $oOrderItem->getTaxAmount();
    $fResult += $oOrderItem->getWeeeTaxAppliedRowAmount(); 
    return $fResult;
  }  
  
}
?>