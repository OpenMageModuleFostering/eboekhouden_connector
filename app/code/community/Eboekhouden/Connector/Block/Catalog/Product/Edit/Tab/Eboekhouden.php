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
class Eboekhouden_Connector_Block_Catalog_Product_Edit_Tab_Eboekhouden extends Mage_Adminhtml_Block_Widget_Form { // Mage_Adminhtml_Block_Widget_Grid {

  public function __construct() {
    parent::__construct();
    $this->setUseAjax(true);
  }
  
  protected function _prepareForm() {
    $oProduct = Mage::registry('product'); /* @var $oProduct Mage_Catalog_Model_Product */
    $oForm = new Varien_Data_Form();
    $oFieldset = $oForm->addFieldset('eboekhouden', array('legend'=>Mage::helper('eboekhouden')->__('Instellingen voor e-Boekhouden.nl')));

    $sCurValue = $oProduct->getData('eboekhouden_grootboekrekening');
    if (empty($sCurValue)) {
      $sCurValue = '8000';
    }    
    $oFieldset->addField('eboekhouden_grootboekrekening', 'select', array(
          'label' => Mage::helper('eboekhouden')->__('Grootboekrekening'),
          'name' => 'product[eboekhouden_grootboekrekening]',
          'class' => 'required-entry',
          'values' => $this->getData('aGbcodes'),
          'value' => $sCurValue
    ));    
    $this->setForm($oForm);    
  }   
  
}

?>
