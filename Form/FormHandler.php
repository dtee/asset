<?php
namespace Odl\AssetBundle\Form;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\FormFactory;

abstract class FormHandler
{
    protected $formFactory;
    protected $request;
    protected $ajaxErrorProvider;

    protected $errors;
    protected $form;

    public function __construt(FormFactory $formFactory, Request $request, AjaxErrorProvider $ajaxErrorProvider)
    {
        $this->formFactory = $formFactory;
        $this->request = $request;
        $this->ajaxErrorProvider = $ajaxErrorProvider;

        $this->createForm();
        $this->process();
    }

    abstract protected function createForm()
    {

    }

    abstract protected function onSuccess() {

    }

    public function process()
    {
        if ($this->request->getMethod() == 'POST')
        {
            $this->form->bindRequest($this->request);
            if ($this->form->isValid()) {
                return $this->onSuccess();
            }
            else {
                $this->errors = $this->ajaxErrorProvider->getErrors($this->form);
            }
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getForm()
    {
        return $this->form;
    }
}