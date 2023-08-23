<?php
/***
 *
 * This file is part of Qc Info rights project.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 <techno@quebec.ca>
 *
 ***/
namespace Qc\QcInfoRights\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class PermissionsViewHelper
 *
 * @package \Qc\QcInfoRights\ViewHelpers
 */
class PermissionsViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var array Cached labels for a single permission mask like "Delete page"
     */
    protected static $permissionLabels = [];

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('permission', 'int', 'Current permission', true);
        $this->registerArgument('scope', 'string', '"user" / "group" / "everybody"', true);
        $this->registerArgument('pageId', 'int', '', true);
    }

    /**
     * Return permissions.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $masks = [1, 16, 2, 4, 8];

        if (empty(static::$permissionLabels)) {
            foreach ($masks as $mask) {
                static::$permissionLabels[$mask] = LocalizationUtility::translate(
                    'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:' . $mask,
                    'be_user'
                );
            }
        }

        $icon = '';
        foreach ($masks as $mask) {
            if ($arguments['permission'] & $mask) {
                $permissionClass = 'fa-check text-success';
                $mode = 'delete';
            } else {
                $permissionClass = 'fa-times text-danger';
                $mode = 'add';
            }

            $label = static::$permissionLabels[$mask];
            $icon .= '<span'
                . ' aria-label="' . htmlspecialchars((string) $label) . ', ' . htmlspecialchars($mode) . ', ' . htmlspecialchars((string) $arguments['scope']) . '"'
                . ' title="' . htmlspecialchars((string) $label) . '"'
                . ' class="t3-icon change-permission fa ' . htmlspecialchars($permissionClass) . '"></span>';
        }

        return '<span id="' . htmlspecialchars($arguments['pageId'] . '_' . $arguments['scope']) . '">' . $icon . '</span>';
    }
}

