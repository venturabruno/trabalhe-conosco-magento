<?php
 
class BV_Trabalhe_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();   
 
        $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'internit.trabalhe',
            array(
                'template' => 'bv/trabalhe.phtml'
            )
        );
 
        $this->getLayout()->getBlock('content')->append($block);
 
        $this->_initLayoutMessages('customer/session');
 
        $this->renderLayout();
    }
    
    private function uploadFile()
    {
    	$fileName = '';
    	if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
			$fileName       = $_FILES['attachment']['name'];
			$fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
			$fileNamewoe    = rtrim($fileName, $fileExt);
			$fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
    			 
			$uploader       = new Varien_File_Uploader('attachment');
			$uploader->setAllowedExtensions(array('doc', 'docx'));
			$uploader->setAllowRenameFiles(false);
			$uploader->setFilesDispersion(false);
			$path = Mage::getBaseDir('media') . DS . 'trabalhe';
			
			if(!is_dir($path)){
				mkdir($path, 0777, true);
			}
			$uploader->save($path . DS, $fileName );
    	}
    	return $fileName;
	}
	
	private function sendEmail($email , $postObject, $fileName = "")
	{
		$emailTemplate  = Mage::getModel('core/email_template');
		
		$emailTemplate->loadDefault('intenit_trabalhe_template');
		$emailTemplate->setTemplateSubject('Trabalhe conosco');
		
		$emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
		$emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
		
		$attachmentFilePath = Mage::getBaseDir('media'). DS . 'trabalhe' . DS . $fileName;
		if(file_exists($attachmentFilePath) && $fileName != ""){
			$fileContents = file_get_contents($attachmentFilePath);
			$attachment   = $emailTemplate->getMail()->createAttachment($fileContents);
			$attachment->filename = $fileName;
		}
		
		return $emailTemplate->send(Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'), array('data' => $postObject));
	}
	
    public function sendemailAction()
    {
    	$post = $this->getRequest()->getPost();
    	if ( $post ) {
    		$translate = Mage::getSingleton('core/translate');
    		$translate->setTranslateInline(false);
    		try {
    			$postObject = new Varien_Object();
    			$postObject->setData($post);
    	
    			$error = false;
    			$fileName = "";
    	
    			if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
    				$error = true;
    			}
    	
    			if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
    				$error = true;
    			}
    	
    			if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
    				$error = true;
    			}
    			
    			if($error){
    				Mage::throwException($this->__('Invalid form data.'));
    			}
    			
    			try{
    				$fileName = $this->uploadFile();
    			}catch (Exception $e) {
    				Mage::throwException($this->__('Formato de arquivo inválido.'));
	    		}
	    		
	    		if (!$this->sendEmail($post['email'], $postObject, $fileName)) {
	    			Mage::throwException($this->__('Erro ao enviar o e-mail.'));
	    		}

    			$translate->setTranslateInline(true);
    	
    			Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
    			$this->_redirect('*/*/');
    	
    			return;
    		} catch (Exception $e) {
    			$translate->setTranslateInline(true);
    	
    			Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__($e->getMessage()));
    			$this->_redirect('*/*/');
    			return;
    		}
    	
    	} else {
    		$this->_redirect('*/*/');
    	}
    }
}
 
?>