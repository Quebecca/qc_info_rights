<?php
declare(strict_types=1);

namespace Qc\QcInfoRights\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
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
        $requestGroupPaginationPage = $request->getParsedBody()['groupPaginationPage'] ?? $request->getQueryParams()['groupPaginationPage'] ?? null;
        if ($requestGroupPaginationPage != null ){
            $groupPaginationCurrentPage = (int)$requestGroupPaginationPage;
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
            'showExportXlsxGroups' => $this->showExportXlsxGroups && $this->xlsxExportService->isAvailable(),
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
            $arrayData[] = mb_convert_encoding($item->getTitle(), 'ISO-8859-15', 'UTF-8');
            $arrayData[] = mb_convert_encoding($item->getDescription(), 'ISO-8859-15', 'UTF-8');
            $arrayData[] = $item->getHidden() ? $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'yes') : $this->localizationUtility->translate(Self::MODULE_LANG_FILE . 'no');
            //Write Inside Our CSV File
            fputcsv($fp, $arrayData, $this->delimiter, $this->quote);
        }
        fclose($fp);

        return $response;
    }

    /**
     * Export Backend user group list as XLSX file.
     *
     * @return ResponseInterface
     */
    public function exportBackendUserGroupListXlsxAction(): ResponseInterface
    {
        $filename = 'Backend-user-group-list-Export-' . date('Y-m-d_H-i') . '.xlsx';

        // Translated headers (UTF-8, no charset conversion)
        $langKeys = ['csvHeader.uid', 'csvHeader.title', 'csvHeader.description', 'csvHeader.isHidden'];
        $headers = array_map(
            fn(string $key) => $this->localizationUtility->translate(self::MODULE_LANG_FILE . $key),
            $langKeys
        );

        // Render full list of Backend User Groups
        $beUserGroups = $this->backendUserGroupRepository->findAll();

        $rows = [];
        foreach ($beUserGroups as $item) {
            $rows[] = [
                $item->getUid(),
                $item->getTitle(),
                $item->getDescription(),
                $item->getHidden()
                    ? $this->localizationUtility->translate(self::MODULE_LANG_FILE . 'yes')
                    : $this->localizationUtility->translate(self::MODULE_LANG_FILE . 'no'),
            ];
        }

        return $this->xlsxExportService->generateResponse($headers, $rows, $filename);
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
