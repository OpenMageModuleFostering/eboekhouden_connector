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
class Eboekhouden_Connector_Block_System_Config_Form_Field extends Mage_Adminhtml_Block_System_Config_Form_Field {

  /*
   * @param Varien_Data_Form_Element_Abstract $element
   * @return string
   */  
  protected function _getElementHtml(Varien_Data_Form_Element_Abstract $oElement)
  {
    $aMatch = array();
    if (preg_match('/^groups\[vatcodes\]\[fields\]\[vatcode_(\w+)\]\[value\]$/',$oElement->getData('name'),$aMatch)) {
      $sResult = '';      
      $sResult .= '<select id="'.$oElement->getHtmlId().'" name="'.$oElement->getName().'" '.$oElement->serialize($oElement->getHtmlAttributes()).'/>'."\n";
      $sSelected = ($oElement->getValue() == '=NOT-USED=') ? ' selected="selected"' : '';
      $sResult .= '<option value="=NOT-USED="'.$sSelected.'>-- '.Mage::helper('eboekhouden')->__('niet in gebruik').' --</option>'."\n";        
      $oTaxCollection = Mage::getModel('tax/calculation_rate')->getCollection()->load(); /* @var $oTaxCollection Mage_Tax_Model_Mysql4_Calculation_Rate_Collection */
      $aTaxItems = $oTaxCollection->getItems();
      foreach ($aTaxItems as $oTaxItem) { /* @var $oTaxItem Mage_Tax_Model_Calculation_Rate */
        $sSelected = ($oElement->getValue() == $oTaxItem->getData('code')) ? ' selected="selected"' : '';
        $sResult .= '<option value="'.htmlspecialchars($oTaxItem->getData('code')).'"'.$sSelected.'>'.htmlspecialchars($oTaxItem->getData('code')).' - '.sprintf('%.02f',$oTaxItem->getData('rate')).'%</option>'."\n";        
      }
      $sResult .= '</select>'."\n";
      $sResult .= $oElement->getAfterElementHtml();      
    }
    else {
      $sResult = $oElement->getElementHtml();      
    }
    return $sResult;
  }
  
}