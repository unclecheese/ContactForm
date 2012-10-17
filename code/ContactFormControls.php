<?php
class ContactFormControls extends Extension
{
	
	public function doContactSubmit($data,$form)
	{
		
		if($func = $form->getOnBeforeSend()) {
		   if(Controller::curr()->hasMethod($func)) {
		     $result = Controller::curr()->$func($data,$form);
		     if($result === false) return $this->owner->redirectBack();
		   }
		}
	

		if($form->hasSpamProtection() && trim(strtolower($data['Spam'])) != 'hot') {
			$form->sessionMessage('Please enter a valid response for the spam protection.','bad');
			  if(isset($_POST['uploaded_files']) && is_array($_POST['uploaded_files'])) {
    			   foreach($_POST['uploaded_files'] as $file_id) {
			   			if($file = File::get()->byID($file_id)->first())
				      		$file->delete();
		   			}
			  }
			
			return $this->owner->redirectBack();
		}

		else {
			
			$this->sendEmail($data,$form);
			
			if($func = $form->getOnAfterSend()) {
			   if(Controller::curr()->hasMethod($func)) {
			     Controller::curr()->$func($data,$form);
			   }
			}

			if($form->getSuccessURL())
				return $this->owner->redirect($form->getSuccessURL());
			else {
				if(Director::is_ajax())
					return new SS_HTTPResponse($form->getSuccessMessage());
				elseif(!$form->removeFormOnSuccess())
					$form->sessionMessage(strip_tags($form->getSuccessMessage()), 'good');
				return $this->owner->redirect(Controller::curr()->Link()."?success=1");
			}
		}
	

	}
	
	public function sendEmail($data,$form) 
	{
		
		if($form->hasNewsletter()) {
				$newsletters = $form->getNewsletters();
				for($i = 0; $i < sizeof($newsletters);$i++) {
					if(isset($_POST['Newsletters'][$i]) && $_POST['Newsletters'][$i] == $i) {
						$newsletter = $newsletters[$i];
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $newsletter['URL']);
						curl_setopt($curl, CURLOPT_POST,1);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $newsletter['Name'].'='.$data['Name'].'&'.$newsletter['Email'].'='.$data['Email']);
						$response = curl_exec($curl);
						curl_close($curl);
					}
				}				
			}
			$email = new Email();
			$email->to = $form->getTo();
			$email->subject = (isset($data['Subject']) && !empty($data['Subject'])) ? $data['Subject'] : $form->getSubject();
			$email->from = (isset($data['Email']) && !empty($data['Email'])) ? $data['Email'] : $form->getFrom();
			$email->ss_template = 'ContactPageEmail';
			$fields = new ArrayList();
			$uploadedFiles = array();
			foreach($form->Fields()->dataFields() as $field) {
				if(!in_array($field->getName(), $form->getOmittedFields())) {
					if($field instanceof CheckboxField) {
						$value = $field->value ? "Yes" : "No";
					}
					else if($field instanceof UploadifyField) {
					    $uploadedFiles[] = $field->Value();
					}
					else {
						$value = nl2br($field->Value());
					}
					if(is_array($value)) {
						$answers = new ArrayList();
						foreach($value as $v)
							$answers->push(new ArrayData(array('Value' => $v)));
						$answers->Checkboxes = true;
						$fields->push(new ArrayData(array('Label' => $field->Title(), 'Values' => $answers)));
					}			
					else
						$title = $field->Title() ? $field->Title() : $field->getName();
						$fields->push(new ArrayData(array('Label' => $title, 'Value' => $value)));
				}
			}

			$email->populateTemplate(new ArrayData(array (
				'IntroText' => $form->getIntroText(),
				'Fields' => $fields,
				'Domain' => Director::protocolAndHost()
			)));
			if (isset($_POST['uploaded_files'])) {
			    $uploadedFiles = array_merge($_POST['uploaded_files'], $uploadedFiles);
			}
			foreach($uploadedFiles as $file_id) {
			    if($file = File::get()->byID($file_id)->first())
			    $email->attachFile(Director::baseFolder() . "/" . $file->Filename, basename($file->Filename));
			}
			
			$email->send();			
            foreach($uploadedFiles as $file_id) {
		        if($file = File::get()->byID($file_id)->first())
	            $file->delete();
	        }
			
	}
	
	public function IsSuccess()
	{
		return isset($_REQUEST['success']) && $_REQUEST['success'] == 1;
	}
}

