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
class Eboekhouden_Connector_Export_SaleController extends Mage_Adminhtml_Controller_Action
{

  public function orderExportAction()
  {
    $aOrderIds = $this->getRequest()->getPost('order_ids', array());
    $oExportModel = Mage::getModel('eboekhouden/export_sales');
    list($iOrdersAdded,$iOrdersExist,$sErrorMsg) = $oExportModel->exportOrders($aOrderIds);
    $this->_reportExportResult($iOrdersAdded,$iOrdersExist,$sErrorMsg);
    $this->_redirectReferer();
  }

  public function invoiceExportAction()
  {
    $aInvoiceIds = $this->getRequest()->getPost('invoice_ids', array());
    $oExportModel = Mage::getModel('eboekhouden/export_sales');
    list($iOrdersAdded,$iOrdersExist,$sErrorMsg) = $oExportModel->exportInvoices($aInvoiceIds);
    $this->_reportExportResult($iOrdersAdded,$iOrdersExist,$sErrorMsg);
    $this->_redirectReferer();
  }  
  
  protected function _reportExportResult($iOrdersAdded,$iOrdersExist,$sErrorMsg) {
    $iOrdersTransferred = $iOrdersAdded+$iOrdersExist;
    
    $sMessage = '<b>'.Mage::helper('eboekhouden')->__('Export naar e-Boekhouden').'</b><br /><br />'."\n";
    if (1 == $iOrdersTransferred) {
      $sMessage .= Mage::helper('eboekhouden')->__('1 mutatie doorgegeven'); 
    }
    else {
      $sMessage .= Mage::helper('eboekhouden')->__('%s mutaties doorgegeven',$iOrdersTransferred);       
    }        
    if (1 == $iOrdersExist) {
      $sMessage .= Mage::helper('eboekhouden')->__(', waarvan er 1 al bestond');
    }
    elseif (1 < $iOrdersExist) {
      $sMessage .= Mage::helper('eboekhouden')->__(', waarvan er %s al bestonden',$iOrdersExist);
    }
    $sMessage .= '.<br />'."\n";

    if (empty($sErrorMsg)) {
      $this->_getSession()->addSuccess($sMessage);
    }
    else {
      $sMessage .= '<br />'."\n";
      $sMessage .= nl2br($sErrorMsg)."\n";
      $this->_getSession()->addError($sMessage);
    }
    $this->_redirectReferer();    
  }
  
}
?>