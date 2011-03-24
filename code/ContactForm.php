<?php

class ContactForm extends Form
{

	protected $to;
	protected $from;
	protected $subject = "New Contact Form";
	protected $introText = "A user has submitted a contact form from your website. His/her information appears below.";
	protected $successMessage = "Thank you for submitting your contact form.";
	protected $successURL;
	protected $spamProtection = true;
	protected $removeFormOnSuccess = false;
	protected $newsletter = false;
	protected $newsletters = array();
	protected $insertPoint = "Spam";
	protected $title;
	protected $omittedFields = array ('Spam','SecurityID', 'Newsletters','uploaded_files');
	protected $callback;
	protected $onBeforeSend;
	protected $onAfterSend;
	
	function __construct($name, $to, $subject)
	{
		parent::__construct(
			Controller::curr(),
			$name,
			new FieldSet(
				new TextField('Name','Your name'),
				new EmailField('Email','Your email address'),
				new TextareaField('Message','Message'),
				new TextField('Spam','Spam protection <span class="required">*</span>: <em>Is fire hot or cold?</em>')

			),
			new FieldSet(
				new FormAction('doContactSubmit','Submit')
			),
			new RequiredFields(
				'Message','Spam','Email'			
			)
			
		);
		$this->setTo($to);
		$this->setSubject($subject);
	}
	
	public function getTo()
	{
		return $this->to;
	}
	
	public function getFrom()
	{
		return $this->from;
	}
	
	public function getSubject()
	{
		return $this->subject;
	}
	
	public function setTo($to)
	{
		$this->to = $to;
	}
	public function setFrom($from)
	{
		$this->from = $from;
	}
	
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	public function setTitle($title)
	{
  	 $this->title = $title;
	}
	
	public function getTitle()
	{
	   return $this->title;
	}
	
	public function setInsertPoint($point)
	{
	   $this->insertPoint = $point;
	}
	
	public function getIntroText()
	{
		return $this->introText;
	}
	
	public function setIntroText($text)
	{
		$this->introText = $text;
	}
	
	public function setSuccessMessage($msg)
	{
		$this->successMessage = $msg;
	}
	
	public function getSuccessMessage()
	{
		return $this->successMessage;
	}
	
	public function setSuccessURL($URLSegment)
	{
		$this->successURL = $URLSegment;
	}
	
	public function getSuccessURL()
	{
		return $this->successURL;
	}
	
	public function removeSpamProtection()
	{
		$this->spamProtection = false;
		$this->Fields()->removeByName('Spam');
	}
	
	public function removeFormOnSuccess()
	{
		return $this->removeFormOnSuccess;
	}
	
	public function setRemoveFormOnSuccess($bool)
	{
		$this->removeFormOnSuccess = $bool;
	}
	
	public function hasSpamProtection()
	{
		return $this->spamProtection;
	}
	
  /**
   * DEPRECATED: Use setInsertPoint() and addField()
   */

	public function addFieldBefore($field, $before = "Message", $required = false)
	{
		$this->Fields()->insertBefore($field, $before);
		if($required)
			$this->addRequiredField($field->Name());
	}
	
	public function addField($field, $required = false, $before = null)
	{
    if($before === null) $before = $this->insertPoint;
    $this->addFieldBefore($field, $before, $required);
    if($field instanceof SWFUploadField)
      $this->addOmittedField($field);
    $field->setForm($this);
	}
	
	public function removeField($field)
	{
		$this->Fields()->removeByName($field);
	}
	
	public function removeDefaultFields()
	{
	   $this->removeField('Name');
	   $this->removeField('Email');
	   $this->removeField('Message');	   	   
	}
	
	public function addRequiredField($fieldName)
	{
		$this->getValidator()->addRequiredField($fieldName);
		foreach($this->Fields() as $field) {
			if ($fieldName == $field->Name()) {
				$field->setTitle($field->Title() . " <span class=\"required\">*</span>");
			}
		}
	}
	
	public function setNewsletter($bool)
	{
		$this->newsletter = true;
	}
	
	public function hasNewsletter()
	{
		return !empty($this->newsletters);
	}
	
	public function getNewsletters()
	{
		return $this->newsletters;
	}
	
	public function addOmittedField($field)
	{
    $name = (is_object($field)) ? $field->Name() : $field;      
    if(!in_array($name, $this->omittedFields))
  	  $this->omittedFields[] = $name;
	}
	
	public function getOmittedFields()
	{
	  return $this->omittedFields;
	}
	
	public function addNewsletterSignup($keys, $label = "Sign me up for the newsletter", $checked = false, $before = "Message")
	{
		if(is_array($keys)) {
			$this->newsletters[] = $keys;
			$index = sizeof($this->newsletters)-1;
			if(!$checkboxes = $this->Fields()->fieldByName('Newsletters')) {
				$c = new CheckboxSetField('Newsletters','', array($index => $label), array($index => $checked ? $index : '999'));
				$this->addFieldBefore($c, $before);
			}
			else {
				$map = $checkboxes->getSource();
				$map[$index] = $label;
				$checkboxes->setSource($map);
				if($checked) {
					$vals = $checkboxes->Value();
					$vals[$index] = $index;
					$checkboxes->setValue($vals);
				} 
			}
		}
	}
	
	/**
	 * DEPRECATED. Use setOnAfterSend() and setOnBeforeSend()
	 */
	public function setCallback($func)
	{
	   $this->onAfterSend = $func;
	}
	
	public function getCallback()
	{
	   return $this->onAfterSend;
	}
	
	public function setOnAfterSend($func)
	{
		$this->onAfterSend = $func;
	}
	
	public function getOnAfterSend()
	{
		return $this->onAfterSend;
	}

	public function setOnBeforeSend($func)
	{
		$this->onBeforeSend = $func;
	}
	
	public function getOnBeforeSend()
	{
		return $this->onBeforeSend;
	}

	
	public function forTemplate()
	{
		return (isset($_REQUEST['success']) && $_REQUEST['success'] == 1 && $this->removeFormOnSuccess()) ? 
			"<div class='success'>".$this->getSuccessMessage()."</div>" :
			parent::forTemplate();
	}
	
	public function validate()
	{
		$result = parent::validate();
		if(!$result) {
		  if(isset($_POST['uploaded_files']) && is_array($_POST['uploaded_files'])) {
			   foreach($_POST['uploaded_files'] as $file_id) {
		   			if($file = DataObject::get_by_id("File",$file_id))
			      		$file->delete();
	   			}
		  }		
		}
		return $result;
	}
	

}
