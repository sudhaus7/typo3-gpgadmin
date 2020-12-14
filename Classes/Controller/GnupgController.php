<?php

//declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 16.08.18
 * Time: 21:25
 */

namespace SUDHAUS7\Sudhaus7Gpgadmin\Controller;

use Exception;
use SUDHAUS7\Sudhaus7Gpgadmin\Traits\Gnupg;
use Swift_SwiftException;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;

/**
 * Class GnupgController
 * @package SUDHAUS7\Sudhaus7Gpgadmin\Controller
 */
class GnupgController extends ActionController
{
    use Gnupg;
    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;
    /**
     * @var BackendTemplateView
     */
    protected $view;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Injects the object manager
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->arguments = $this->objectManager->get(Arguments::class);
    }
    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        //$this->generateMenu();
        //$this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        }
    }

    /**
     * @throws Swift_SwiftException
     */
    public function initializeAction()
    {
        $this->initGnu();
    }

    public function indexAction()
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(
            IconFactory::class
        );
        $docHeader = $this->view->getModuleTemplate()->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                      ->setHref($this->uriBuilder->uriFor('add'))
                      ->setShowLabelText($this->getLanguageService()
                                              ->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.new'))
                      ->setIcon($iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL))
                      ->setTitle($this->getLanguageService()
                                      ->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.new')),
            ButtonBar::BUTTON_POSITION_LEFT
        );

        $search = (string)GeneralUtility::_POST('tx_sudhaus7gpgadmin_system_sudhaus7gpgadmintxsudhaus7gpgadmin')['search'];
        $keys = $this->gnupg->keyinfo('');
        if (!empty($keys) && !empty($search)) {
            $keys = $this->gnupg->keyinfo($search);
            if (empty($keys)) {
                $this->addFlashMessage(
                    $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:index.nokeys'),
                    null,
                    AbstractMessage::INFO
                );
            }
        }
        $assignedValues = [
            'keys' => $keys,
            'search' => $search
        ];
        $this->view->assignMultiple($assignedValues);
    }

    /**
     * @param string $key
     * @param bool $allowsecret
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     * @throws Exception
     */
    public function deleteAction($key, $allowsecret = false)
    {
        try {
            if ($this->gnupg->deletekey($key, $allowsecret)) {
                $this->addFlashMessage(
                    $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:delete.yes'),
                    $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:delete.key'),
                    AbstractMessage::NOTICE
                );
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 1536247545);
        }

        $this->redirect('index');
    }

    public function addAction()
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(
            IconFactory::class
        );
        $docHeader = $this->view->getModuleTemplate()->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                      ->setHref($this->uriBuilder->uriFor('index'))
                      ->setShowLabelText($this->getLanguageService()
                                              ->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.back'))
                      ->setIcon($iconFactory->getIcon('actions-close', Icon::SIZE_SMALL))
                      ->setTitle($this->getLanguageService()
                                      ->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.back')),
            ButtonBar::BUTTON_POSITION_LEFT
        );
    }

    /**
     * @throws Exception
     */
    public function addKeyAction()
    {
        $newKey = GeneralUtility::_POST('tx_sudhaus7gpgadmin_system_sudhaus7gpgadmintxsudhaus7gpgadmin')['newkey'];
        try {
            $this->gnupg->import($newKey);
        } catch (Exception $exception) {
            throw new Exception('Your key is not valid. Please make sure, your key does match a valid PGP/GPG Public Key.', 1535122162);
        }
        $this->addFlashMessage(
            $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:addKey.yes'),
            $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:addKey.added'),
            AbstractMessage::INFO
        );
        $this->redirect('index');
    }

    /**
     * Creates te URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the language service
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
