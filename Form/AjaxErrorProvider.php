<?php
namespace Odl\AssetBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Translation\TranslatorInterface;

class AjaxErrorProvider
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getErrors(Form $form, &$errors = array())
    {
        $formView = $form->createView();
        $key = $formView->get('id');

        if (!$form->isValid())
        {
            // User translator to translate
            $errorObjects = $form->getErrors();
            $formTypes = $formView->get('types');
            if (in_array('repeated', $formTypes)) {
                //$key .= '_' . $formView->get('name');
            }

            foreach ( $errorObjects as $errorObject )
            {
                $errors[$key][] = $this->translator->trans($errorObject->getMessageTemplate(), $errorObject->getMessageParameters(), 'validators');
            }
        }
        else
        {
            $errors[$key] = null;
        }

        foreach ( $form->getChildren() as $childName => $form )
        {
            $this->getErrors($form, $errors);
		}

		return $errors;
	}
}