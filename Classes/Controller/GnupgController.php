<?php
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 16.08.18
 * Time: 21:25
 */

namespace SUDHAUS7\Sudhaus7Gpgadmin\Controller;

use SUDHAUS7\Sudhaus7Gpgadmin\Traits\Gnupg;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendTemplateView;

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
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Injects the object manager
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->arguments = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class);
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
     * @throws \Swift_SwiftException
     */
    public function initializeAction() {
        $this->initGnu();
    }

    /**
     * @param string $search
     */
    public function indexAction($search = '')
    {
        $keys = $this->gnupg->keyinfo($search);
        $assignedValues = [
            'keys' => $keys
        ];
        $this->view->assignMultiple($assignedValues);
    }

    protected function generateMenu() {
        $menuItems = [
            'index' => [
                'controller' => 'Gnupg',
                'action' => 'index',
                'label' => 'Übersicht'
            ],
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
     * @throws \Swift_SwiftException
     */
    protected function initGNUPG()
    {
        if (!class_exists('gnupg')) {
            throw new \Swift_SwiftException('PHPMailerPGP requires the GnuPG class');
        }

        if (!$this->gnupgHome && isset($_SERVER['HOME'])) {
            $this->gnupgHome = $_SERVER['HOME'] . '/.gnupg';
        }

        if (!$this->gnupgHome && getenv('HOME')) {
            $this->gnupgHome = getenv('HOME') . '/.gnupg';
        }

        if (!$this->gnupgHome) {
            throw new \Swift_SwiftException('Unable to detect GnuPG home path, please call PHPMailerPGP::setGPGHome()');
        }

        if (!file_exists($this->gnupgHome)) {
            throw new \Swift_SwiftException('GnuPG home path does not exist');
        }

        putenv("GNUPGHOME=" . escapeshellcmd($this->gnupgHome));

        if (!$this->gnupg) {
            $this->gnupg = new \gnupg();
        }

        $this->gnupg->seterrormode(\gnupg::ERROR_EXCEPTION);
    }
}