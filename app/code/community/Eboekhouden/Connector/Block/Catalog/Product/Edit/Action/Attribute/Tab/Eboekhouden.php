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
class Eboekhouden_Connector_Block_Catalog_Product_Edit_Action_Attribute_Tab_Eboekhouden extends Mage_Adminhtml_Block_Catalog_Product_Edit_Action_Attribute_Tab_Attributes {

  public function __construct() {
    parent::__construct();
    $this->setUseAjax(true);
  }
  
  protected function _prepareForm()
  {
    Mage::dispatchEvent('adminhtml_catalog_product_form_prepare_excluded_field_list', array('object'=>$this));

    $oForm = new Varien_Data_Form();
    $oFieldset = $oForm->addFieldset('eboekhouden', array('legend'=>Mage::helper('eboekhouden')->__('Instellingen voor e-Boekhouden.nl')));
     
    $oForm->setDataObject(Mage::getModel('catalog/product'));

    $oElement = $oFieldset->addField('eboekhouden_grootboekrekening', 'select', array(
              'label' => Mage::helper('eboekhouden')->__('Grootboekrekening'),
              'name' => 'eboekhouden_grootboekrekening',
              'class' => 'required-entry validate-zero-or-greater required-entry input-text',
              'disabled' => 'disabled',
              'values' => $this->getData('aGbcodes'),
              'value' => '8000'    
    ));    
    
    $oElement->setAfterElementHtml($this->_getAdditionalElementHtml($oElement));
    
    $oForm->setFieldNameSuffix('attributes');   
    $this->setForm($oForm);    
  }     
  
  
}