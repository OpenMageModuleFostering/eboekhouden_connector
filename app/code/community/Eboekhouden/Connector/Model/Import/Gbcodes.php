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
class Eboekhouden_Connector_Model_Import_Gbcodes {

  /**
   * Import Grootboek rekening codes from e-Boekhouden.nl.
   *
   * @return array with ($aResult,$sErrorMsg). $aResult: key=code value=desc 
   */
  public function importGbcodes() {
    $aResult = array();
    $sErrorMsg = '';
    
    $oClient = new Zend_Http_Client();
    $oClient->setUri('https://secure.e-boekhouden.nl/bh/api.asp');
    
    $sConUser = trim(Mage::getStoreConfig('eboekhouden/connector/username'));
    $sConWord = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode1'));
    $sConGuid = trim(Mage::getStoreConfig('eboekhouden/connector/securitycode2'));
    
    if ( empty($sConUser) || empty($sConWord) || empty($sConGuid) ) {
      $sErrorMsg .= Mage::helper('eboekhouden')->__('Configuratie is niet volledig ingevuld, ga naar het menu "%s","%s" en kies "e-Boekhouden.nl" uit de zijbalk. Vul de gegevens in onder "Connector Login Gegevens"',Mage::helper('adminhtml')->__('System'),Mage::helper('adminhtml')->__('Configuration'));
    }
    else {    
      $sXml = '<?xml version="1.0"?>'."\n";
      $sXml .= '
<API>
  <ACTION>LIST_GBCODE</ACTION>
  <VERSION>1.0</VERSION>
  <SOURCE>Magento</SOURCE>
  <AUTH>
    <GEBRUIKERSNAAM>'.$sConUser.'</GEBRUIKERSNAAM>
    <WACHTWOORD>'.$sConWord.'</WACHTWOORD>
    <GUID>'.$sConGuid.'</GUID>
  </AUTH>
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

        // Enable for debugging:
        //$sErrorMsg .= str_replace('  ',' &nbsp;',htmlspecialchars($sResponse));
        
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
          elseif (!isset($oData->RESULT->GBCODES->GBCODE)) {
            $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout in van API ontvangen XML: RESULT.GBCODES.GBCODE is niet gevonden')."\n";
          }
          elseif (empty($oData->RESULT->GBCODES->GBCODE)) {
            $sErrorMsg .= Mage::helper('eboekhouden')->__('Fout in van API ontvangen XML: RESULT.GBCODES.GBCODE is leeg')."\n";
          }
          else {
            foreach ($oData->RESULT->GBCODES->GBCODE as $oGbCode) {
              $sCode = strval($oGbCode->CODE);
              $sDesc = $sCode.' - '.strval($oGbCode->OMSCHRIJVING);
              $aResult[$sCode] = $sDesc;
            }
          }
        }
      }            
    }
    return array($aResult,$sErrorMsg);
  }
  
}
?>