<?php
declare(strict_types=1);
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
     * @var \TYPO3\CMS\Core\Log\Logger
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
        $this->generateMenu();
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
        $search = GeneralUtility::_POST('tx_sudhaus7gpgadmin_web_sudhaus7gpgadmintxsudhaus7gpgadmin')['search'];
        $keys = $this->gnupg->keyinfo($search);
        if (empty($keys)) {
            $this->addFlashMessage(
                $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:index.nokeys'),
                null,
                AbstractMessage::INFO
            );
            $keys = $this->gnupg->keyinfo('');
        }
        $assignedValues = [
            'keys' => $keys
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
        $success = false;
        try {
            if ($this->gnupg->deletekey($key, $allowsecret)) {
                $this->addFlashMessage(
                    $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:delete.yes'),
                    $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:delete.key'),
                    AbstractMessage::NOTICE
                );
                $success = true;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 1536247545);
        }

        $this->redirect('index');
    }

    public function addAction()
    {
    }

    /**
     * @throws Exception
     */
    public function addKeyAction()
    {
        $newKey = GeneralUtility::_POST('tx_sudhaus7gpgadmin_web_sudhaus7gpgadmintxsudhaus7gpgadmin')['newkey'];
        try {
            $this->gnupg->import($newKey);
        } catch (Exception $exception) {
            throw new Exception('Your key isn\'t valid. Please make sure, your key does match a valid string.', 1535122162);
        }
        $this->addFlashMessage(
            $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:addKey.yes'),
            $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:addKey.added'),
            AbstractMessage::INFO
        );
        $this->redirect('index');
    }

    protected function generateMenu()
    {
        $menuItems = [
            'index' => [
                'controller' => 'Gnupg',
                'action' => 'index',
                'label' => $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.overview')
            ],
            'add' => [
                'controller' => 'Gnupg',
                'action' => 'add',
                'label' => $GLOBALS['LANG']->sL('LLL:EXT:sudhaus7_gpgadmin/Resources/Private/Language/locallang.xlf:menu.new')
            ]
        ];
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('GnupgMenu');
        foreach ($menuItems as  $menuItemConfig) {
            if ($this->request->getControllerName() === $menuItemConfig['controller']) {
                $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'] ? true : false;
            } else {
                $isActive = false;
            }
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->getHref($menuItemConfig['controller'], $menuItemConfig['action']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
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
     * @throws Swift_SwiftException
     */
    protected function initGNUPG()
    {
        if (!class_exists('gnupg')) {
            throw new Swift_SwiftException('PHPMailerPGP requires the GnuPG class', 1535122998);
        }

        if (!$this->gnupgHome && isset($_SERVER['HOME'])) {
            $this->gnupgHome = $_SERVER['HOME'] . '/.gnupg';
        }

        if (!$this->gnupgHome && getenv('HOME')) {
            $this->gnupgHome = getenv('HOME') . '/.gnupg';
        }

        if (!$this->gnupgHome) {
            throw new Swift_SwiftException('Unable to detect GnuPG home path, please call PHPMailerPGP::setGPGHome()', 1535123005);
        }

        if (!file_exists($this->gnupgHome)) {
            throw new Swift_SwiftException('GnuPG home path does not exist', 1535123009);
        }

        putenv("GNUPGHOME=" . escapeshellcmd($this->gnupgHome));

        if (!$this->gnupg) {
            $this->gnupg = new \gnupg();
        }

        $this->gnupg->seterrormode(\gnupg::ERROR_EXCEPTION);
    }
}
