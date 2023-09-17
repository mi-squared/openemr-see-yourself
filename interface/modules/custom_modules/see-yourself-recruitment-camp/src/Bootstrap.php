<?php

namespace Mi2\SeeYourselfCampaign;

use OpenEMR\Menu\PatientMenuEvent;
use OpenEMR\Menu\PatientMenuRole;
use OpenEMR\Modules\CustomModuleSkeleton\GlobalConfig;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Events\RestApiExtend\RestApiResourceServiceEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{

	//This is a comment
   const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";
    const MODULE_NAME = "see-yourself-recruitment-camp/";
    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private $eventDispatcher;

    /**
     * @var string The folder name of the module.  Set dynamically from searching the filesystem.
     */
    private $moduleDirectoryName;

    /**
     * @var \Twig\Environment The twig rendering environment
     */
    private $twig;

    /**
     * @var SystemLogger
     */
    private $logger;

    private string $moduleDir ;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (empty($kernel)) {
            $kernel = new Kernel();
        }
        //set the module directory name
        $this->moduleDirectoryName = basename(dirname(__DIR__));

        //we now have an event dispatacher!
        $this->eventDispatcher = $eventDispatcher;

        //not sure what this is for, but this came from the skeleton module.  Will figure this out later
        //$this->logger = new SystemLogger();
        $this->moduleDir = $GLOBALS['webserver_root'] . self::MODULE_INSTALLATION_PATH . self::MODULE_NAME;
    }

    public static function getModuleDir(){
        return  self::MODULE_INSTALLATION_PATH . self::MODULE_NAME;
    }
    public function subscribeToEvents(): void    {

        $this -> registerSMSMenu();

    }




    public function registerSMSMenu(){
        $this-> eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'registerCallList']);

    }





    public function registerCallList(MenuEvent $event): MenuEvent
    {
        //call the getMenu() method
        $menu = $event->getMenu();
        //get the contents of the json file

        $smsReportMenu =  file_get_contents($this->moduleDir . 'interface/menu/call_list.json');


        $smsReportMenuParsed = json_decode($smsReportMenu);

        //read the menu until we get to the Admin
        foreach ($menu as $index => &$item) {
            if ($item->menu_id == 'admimg') {
                foreach($item->children as $child) {
                    if ($child->label == "Forms") {

                    }
                }
            }
            if ($item->menu_id == 'repimg') {
                $item->children[] = $smsReportMenuParsed[0];
            }
        }

        //we place the SMS admin menu under the Patient Reminders

        //set the menu
        $event->setMenu($menu);


        return $event;
    }


    //****END OF ACTIVE CODE
    //We are going to leave this example as is to use for reference.
    public function addCustomModuleMenuItem(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'mod0';
        $menuItem->label = xlt("Custom Module Skeleton");
        // TODO: pull the install location into a constant into the codebase so if OpenEMR changes this location it
        // doesn't break any modules.
        $menuItem->url = "/interface/modules/custom_modules/module-custom-skeleton/public/sample-index.php";
        $menuItem->children = [];

        /**
         * This defines the Access Control List properties that are required to use this module.
         * Several examples are provided
         */
        $menuItem->acl_req = [];

        /**
         * If you would like to restrict this menu to only logged in users who have access to see all user data
         */
        //$menuItem->acl_req = ["admin", "users"];

        /**
         * If you would like to restrict this menu to logged in users who can access patient demographic information
         */
        //$menuItem->acl_req = ["users", "demo"];


        /**
         * This menu flag takes a boolean property defined in the $GLOBALS array that OpenEMR populates.
         * It allows a menu item to display if the property is true, and be hidden if the property is false
         */
        //$menuItem->global_req = ["custom_skeleton_module_enable"];

        /**
         * If you want your menu item to allows be shown then leave this property blank.
         */
        $menuItem->global_req = [];

        foreach ($menu as $item) {
            if ($item->menu_id == 'modimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $event->setMenu($menu);

        return $event;
    }


}
