<?php

namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
/**
 * Class GroupsController
 *
 * @package \Qc\QcInfoRights\Controller
 */
class GroupsController extends BaseBackendController
{

    /**
     * @var boolean
     */
    protected bool $showExportGroups;

    /**
     * This function is used to handle requests, when no action selected
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        //$this->filter = $this->backendSession->get('qc_info_rights_key');
        if (GeneralUtility::_GP('groupPaginationPage') != null ){
            $groupPaginationCurrentPage = (int)GeneralUtility::_GP('groupPaginationPage');
           // Store the current page on session
           // $this->filter = $this->backendSession->get('qc_info_rights_key');
           // $this->filter->setCurrentGroupsTabPage($groupPaginationCurrentPage);
           // $this->updateFilter();
        }
        else{
            // read from Session
            $groupPaginationCurrentPage = $this->filter->getCurrentGroupsTabPage();
        }

        $groupsWithNumberOfUsers = [];
        $pagination = $this->getPagination($this->backendUserGroupRepository->findAll(), $groupPaginationCurrentPage,$this->groupsPerPage );
        $groups = $pagination['paginatedData'];
        foreach ($groups as $group){
            array_push($groupsWithNumberOfUsers, [
                'group' => $group,
                'numberOfUsers' => count($this->backendUserRepository->getGroupMembers($group->getUid()))
            ]);
        }

        $this->view->assignMultiple([
            'prefix' => 'beUserGroupList',
            'backendUserGroups' => $groupsWithNumberOfUsers,
            'showExportGroups' => $this->showExportGroups,
            'showMembersColumn' => $this->checkShowTsConfig('showMembersColumn'),
            'pagination' => $pagination['pagination'],
            'currentPage' => $this->id,
        ]);

        return $this->view->renderResponse('GroupsInfo');
    }

    /**
     * @return ResponseInterface
     */
    public function exportBackendUserGroupListAction(): ResponseInterface
    {
        //Initialize Response and prepare the CSV output file
        $format = 'csv';
        $title = 'Backend-user-group-list-Export-' . date('Y-m-d_H-i');
        $filename = $title . '.' . $format;

        $response = new Response('php://output', 200,
            ['Content-Type' => 'text/csv; charset=utf-8',
                'Content-Description' => 'File transfer',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );

        //Implement Array Contains Key of the Lang File To regenerate an Array For CSV Header
        $LangArrayHeader = ['csvHeader.uid', 'csvHeader.title', 'csvHeader.description', 'csvHeader.isHidden'];

        //CSV HEADERS Using Translate File and respecting UTF-8 Charset for Special Char
        $headerCsv = $this->generateCsvHeaderArray($LangArrayHeader);

        //Render Fill list of Backend User Group
        $beUserGroup = $this->backendUserGroupRepository->findAll();

        //Open File Based on Function Php To start Write inside the file CSV
        $fp = fopen('php://output', 'wb');

        fputcsv($fp, $headerCsv, $this->delimiter, $this->quote);

        foreach ($beUserGroup as $key => $item) {
            //Fill Array of User by Data
            $arrayData = [];
            $arrayData[] = $item->getUid();
            $arrayData[] = $this->charsetConverter->conv($item->getTitle(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $this->charsetConverter->conv($item->getDescription(), 'utf-8', 'iso-8859-15');
            $arrayData[] = $item->getHidden() ? $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'yes') : $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'no');
            //Write Inside Our CSV File
            fputcsv($fp, $arrayData, $this->delimiter, $this->quote);
        }
        fclose($fp);

        return $response;
    }

    /**
     * This function is used to get the members details for the "Members" column
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showMembers(ServerRequestInterface $request): ResponseInterface{
        $urlParam = $request->getQueryParams();
        $members = $this->backendUserRepository->getGroupMembers($urlParam['groupUid'], $urlParam['selectedColumn']);
        return new JsonResponse($members);
    }
}
