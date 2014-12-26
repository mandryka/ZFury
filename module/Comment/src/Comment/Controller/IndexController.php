<?php

namespace Comment\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Comment\Form;
use Comment\Service;
use Comment\Form\Filter;
use DoctrineModule\Validator;
use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Zend;

class IndexController extends AbstractActionController
{
    /**
     * @return array|ViewModel
     */
    public function indexAction()
    {
        // for POST data
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
        }

        // for GET (or query string) data
        if ($this->getRequest()->getQuery()->entity && $entityId = intval($this->getRequest()->getQuery()->id)) {
            $data = array();
            $data['entity'] = $this->getRequest()->getQuery()->entity;
            $data['entityId'] = $this->getRequest()->getQuery()->id;
        }

        if (!isset($data['entity']) || !isset($data['entityId'])) {
            return $this->notFoundAction();
        }

        $comments = $this->getServiceLocator()
            ->get('Comment\Service\Comment')
            ->lisComments($data);

        $viewModel = new ViewModel(array('comments' => $comments));

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTerminal(true);
        }

        return $viewModel;
    }

    /**
     * @return \Zend\Http\Response
     * @throws \Exception
     */
    public function deleteAction()
    {
        if (!($id = $this->params()->fromRoute('id'))) {
            throw new \Exception("No number comments that removed");
        }

        $result = $this->getServiceLocator()
            ->get('Comment\Service\Comment')
            ->delete($id);

        if ($result) {
            $this->flashMessenger()->addSuccessMessage('Comment deleted');

            //TODO: redirect where?
            $url = $this->getRequest()->getHeader('Referer')->getUri();
            return $this->redirect()->toUrl($url);
        }
    }

    /**
     * @return ViewModel
     * @throws \Exception
     */
    public function editAction()
    {
        if (!$id = $this->params()->fromRoute('id')) {
            throw new \Exception('Bad Request');
        }

        $objectManager = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $comment = $objectManager->getRepository('\Comment\Entity\Comment')->findOneBy(['id' => $id]);

        $form = $this->getServiceLocator()
            ->get('Comment\Service\Comment')->createForm($comment);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $data = $data->toArray();

            $commentEdited = $this->getServiceLocator()
                ->get('Comment\Service\Comment')
                ->edit($form, $comment, $data);

            $flashMessenger = new FlashMessenger();
            if ($commentEdited) {
                $flashMessenger->addSuccessMessage('Comment edited');
                $url = $this->getRequest()->getHeader('Referer')->getUri();
                return $this->redirect()->toUrl($url);
            } else {
                $flashMessenger->addErrorMessage('Comment is not changed');
            }
        }
        $viewModel = new ViewModel(['form' => $form, 'path' => $this->getRequest()->getUri()->getPath()]);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTerminal(true);
        }

        return $viewModel;
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     * @throws \Exception
     */
    public function addAction()
    {
        $form = $this->getServiceLocator()
            ->get('Comment\Service\Comment')->createForm();

        // for POST data
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            // for GET (or query string) data
            if ($this->getRequest()->getQuery()->entity && $entityId = intval($this->getRequest()->getQuery()->id)) {
                $data->set('entity', $this->getRequest()->getQuery()->entity);
                $data->set('entityId', $this->getRequest()->getQuery()->id);
            }

            $data = $data->toArray();
            if (!isset($data['entity']) || !isset($data['entityId'])) {
                return $this->notFoundAction();
            }
            $comment = $this->getServiceLocator()
                ->get('Comment\Service\Comment')
                ->add($form, $data);

            $flashMessenger = new FlashMessenger();
            if ($comment) {
                $flashMessenger->addSuccessMessage('Comment created');
                //TODO: redirect where?
                $url = $this->getRequest()->getHeader('Referer')->getUri();
                return $this->redirect()->toUrl($url);
            } else {
                $flashMessenger->addErrorMessage('Comment is not created');
            }
        }

        $viewModel = new ViewModel(['form' => $form, 'title' => 'Add comment', 'path' => $this->getRequest()->getUri()->getPath().'?'.$this->getRequest()->getUri()->getQuery()]);
        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTerminal(true);
        }

        return $viewModel;
    }
}