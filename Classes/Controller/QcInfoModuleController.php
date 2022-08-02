<?php

namespace Qc\QcInfoRights\Controller;

use Qc\QcInfoRights\Report\AccessRightsReport;
use Qc\QcInfoRights\Report\GroupsReport;
use Qc\QcInfoRights\Report\UsersReport;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;

/**
 * Class QcInfoModuleController
 *
 * @package \Qc\QcInfoRights\Report
 */
class QcInfoModuleController extends \TYPO3\CMS\Info\Controller\InfoModuleController
{
    const QC_PREFIX = "Qc\QcInfoRights\Report";

    /**
     * Generate the ModuleMenu
     */
    protected function generateMenu()
    {
        if($GLOBALS['BE_USER'] != null){
            $this->setQcInfoRightsMenu($this->MOD_MENU['function']);
        }
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebInfoJumpMenu');

        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }

            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * @return bool
     */
    protected function setQcInfoRightsMenu(&$menuItems){
        //Render user TsConfig
        $userTS = $GLOBALS['BE_USER']!= null ? $GLOBALS['BE_USER']->getTSConfig()['mod.']['qcinforights.'] : null;

        //Rendere Page TsConfig by default get first page
        $modTSconfig = BackendUtility::getPagesTSconfig(1)['mod.']['qcinforights.'];

        if(is_array($userTS) || is_array($modTSconfig)){
            //Checking about access
            $showMenuAccess =  (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showMenuAccess');
            $showMenuGroups =  (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showMenuGroups');
            $showMenuUsers =  (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showMenuUsers');

            //@deprecated will removed in the next update v1.3.0
            $showTabAccess =   (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showTabAccess');
            $showTabGroups =  (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showTabGroups');
            $showTabUsers =  (int)$this->checkShowTsConfig($userTS,$modTSconfig,'showTabUsers');

            if(!$showMenuAccess || !$showTabAccess) {
                unset($menuItems[self::QC_PREFIX."\AccessRightsReport"]);
            }

            // Extend Module INFO for Groups tab
            if(!$showTabGroups || !$showMenuGroups) {
                unset($menuItems[self::QC_PREFIX."\GroupsReport"]);
            }

            // Extend Module INFO For Users tab
            if(!$showTabUsers || !$showMenuUsers){
                unset($menuItems[self::QC_PREFIX."\UsersReport"]);
            }
        }
        return true;
    }

    /**
     * PHP function to check and validate the access&right for each mo
     *
     * @param array|null $userTS
     * @param array |null     $modTSconfig
     * @param string |null    $value
     *
     * @return string
     */
    protected function checkShowTsConfig(array $userTS = NULL, array $modTSconfig = NULL, string $value = NULL): string
    {
        if (is_array($userTS) && array_key_exists($value, $userTS)) {
            return $userTS[$value];
        } else if (is_array($modTSconfig) && array_key_exists($value, $modTSconfig)) {
            return $modTSconfig[$value];
        }
        return '';
    }
}