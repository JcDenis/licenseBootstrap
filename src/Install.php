<?php
/**
 * @brief licenseBootstrap, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\licenseBootstrap;

use dcCore;
use dcNamespace;
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade
            self::growUp();

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    public static function growUp(): void
    {
        $current = dcCore::app()->getVersion(My::id());

        // Update settings id, ns
        if ($current && version_compare($current, '2022.12.22', '<')) {
            $record = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'licenseBootstrap' "
            );
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
            while ($record->fetch()) {
                if (in_array($record->f('setting_id'), ['license_head'])) {
                    $cur->setField('setting_value', (string) unserialize(base64_decode($record->f('setting_value'))));
                }
                $cur->setField('setting_ns', My::id());
                $cur->update(
                    "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'licenseBootstrap' " .
                    'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . dcCore::app()->con->escapeStr($record->f('blog_id')) . "' "))
                );
                $cur->clean();
            }
        }
    }
}
