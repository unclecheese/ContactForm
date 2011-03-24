<?php

class ContactFormPage extends Page
{
  static $db = array (
    'To' => 'Varchar(255)',
    'Subject' => 'Varchar(255)',
    'IntroText' => 'Text',    
    'SuccessMessage' => 'HTMLText'
  );
  
  static $defaults = array (
    'To' => 'your@email.com',
    'Subject' => 'New contact form',
    'IntroText' => 'A user has submitted a new contact form from your website. His/her information appears below.',
    'SuccessMessage' => 'Thank you for submitting the contact form!'
  );
  
  public function getCMSFields()
  {
    $f = parent::getCMSFields();
    $f->addFieldsToTab("Root.Content.Contact Form", array(
      new TextField('To','Send form to (comma separated email addresses)'),
      new TextField('Subject','Subject of email'),
      new TextareaField('IntroText','Email intro text'),
      new HtmlEditorField('SuccessMessage','Success message')
    ));
    
    return $f;
  }
  
  
	public static function field_to_code($field) {
		$type = $field->getType() ? BedrockField::correct_field($field->getType()) : "TextField";
		$label = $field->getLabel() ? $field->getLabel() : FormField::name_to_label($field->getKey());
		if(in_array($type, array("OptionsetField","DropdownField","CheckboxSetField"))) {
			$map = array();
			if($field->getMap()) {
				$map = array_combine($field->getMap()->toArray(), $field->getMap()->toArray());
			}
			$third = new VariableValue(BedrockCoder::export_var($map, 3));
		}
		else {
			$third = $field->getValue() ? new LiteralValue($field->getValue()) : null;
		}
		
		$field_code = BedrockCoder::instantiate_object($type, new ArgumentList(
			new LiteralValue($field->getKey()),
			new VariableValue(BedrockCoder::translate($label)),
			$third
		));
		return $field_code;
	}

	public function writePHP(&$file) {
		if($settings = $this->getClassSettings()) {
			if($fields = $settings->getContactFormFields()) {
				$body = array();
				$body[] = BedrockCoder::new_variable("form", new VariableValue(
					BedrockCoder::invoke_static_method("parent", "Form", new ArgumentList())
				));
				$body[] = BedrockCoder::invoke_method("form", "removeDefaultFields", new ArgumentList());
				foreach($fields as $field) {				
					if(substr($field->getKey(),0,10) == "FieldGroup") {
						$args = new ArgumentList();
						foreach($field as $sub) {
							$args->push(new VariableValue(self::field_to_code($sub)));
						}
						$field_code = BedrockCoder::instantiate_multiline_object("FieldGroup", $args);
					}
					else {
						$field_code = self::field_to_code($field);
					}
					$req_arg = $field->getRequired() ? new BooleanValue(true) : null;
					$body[] = BedrockCoder::invoke_method("form", "addField", new ArgumentList(new VariableValue($field_code),$req_arg));
				}
				$body[] = BedrockCoder::return_var("form");
			 	$function = BedrockCoder::new_line();
				$function .= BedrockCoder::write_function("Form", new ArgumentList(), $body);
				$file->code($function, "controller");
			}
		}
	}
  
}

class ContactFormPage_Controller extends Page_Controller
{
  static $allowed_actions = array (
  	'Form'
  );
  public function Form()
  {
    $form = new ContactForm("Form", $this->To, $this->Subject);
    $form->setSuccessMessage($this->SuccessMessage);
    $form->setIntroText($this->IntroText);
    return $form;
  }
}
