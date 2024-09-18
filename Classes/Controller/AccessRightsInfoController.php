<?php

namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AccessRightsController
 *
 * @package \Qc\QcInfoRights\Controller
 */
class AccessRightsInfoController extends BaseBackendController
{

    /**
     * This function is used to handle requests, when no action selected
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {

        $this->init($request);

        $allowedModuleOptions = $this->getAllowedModuleOptions();
        $moduleData = $request->getAttribute('moduleData');
        $depth = (int)($moduleData->get('depth') ?? 0);

        /*Render Tree View For the Access Page with His Depth*/
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_user', true);
        $tree->addField('perms_group', true);
        $tree->addField('perms_everybody', true);
        $tree->addField('perms_userid', true);
        $tree->addField('perms_groupid', true);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if ($this->id) {
            $title = $tree->getTitleAttrib($this->pageinfo);
            $icon = $this->pageinfo['is_siteroot'] ? $iconFactory->getIcon('apps-pagetree-folder-root', Icon::SIZE_SMALL) : $iconFactory->getIconForRecord($tree->table, $this->pageinfo, Icon::SIZE_SMALL);
            $tree->tree[] = ['row' => $this->pageinfo, 'HTML' => $icon->setTitle($title)->render()];
        }else{
            $tree->tree[] = ['row' => $this->pageinfo, 'HTML' => $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render()];
        }

        $tree->getTree($this->id, $depth);

        $beUserArray = BackendUtility::getUserNames();
        $beGroupArray = BackendUtility::getGroupNames();

        $this->view->assignMultiple([
            'prefix' => 'accessRights',
            'viewTree' => $tree->tree,
            'depthSelect' => $allowedModuleOptions['depth'],
            'depthDropdownCurrentValue' => $depth,
            'beUsers' => $beUserArray,
            'pageUid' => $this->id,
            'beGroups' => $beGroupArray,
            'hideUser' => $this->checkShowColumnTsConfig('user'),
            'hideGroup' => $this->checkShowColumnTsConfig('group'),
            'hideEveryBody' => $this->checkShowColumnTsConfig('everybody'),
        ]);
        return $this->view->renderResponse('AccessRightsInfo');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getAllowedModuleOptions(): array
    {
        $lang = $this->getLanguageService();
        return [
            'depth' => [
                0 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
        ];
    }

    /**
     * This function to check if get default or Custom Value
     *
     * @param string $value
     *
     * @return string
     */
    protected function checkShowColumnTsConfig(string $value): string
    {
        if (is_array($this->userTS['qcinforights.'] ?? null)
            && array_key_exists($value, $this->userTS['qcinforights.']['hideAccessRights.'] ?? [])) {
            return $this->userTS['qcinforights.']['hideAccessRights.'][$value];
        } else if (is_array($this->modTSconfig['properties']) && array_key_exists($value, $this->modTSconfig['properties']['hideAccessRights.'])) {
            return $this->modTSconfig['properties']['hideAccessRights.'][$value];
        }
        return '';
    }
}
