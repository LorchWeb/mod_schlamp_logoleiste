<?php
/** @license GNU General Public License version 2 or later */
namespace Schlamp\Module\Logoleiste\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Schlamp\Module\Logoleiste\Site\Helper\LogoHelper;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $data['logos'] = LogoHelper::getLogos($data['params']);

        if ($data['logos'] !== []) {
            $this->getApplication()->getDocument()->getWebAssetManager()
                ->registerAndUseStyle('mod_schlamp_logoleiste', 'mod_schlamp_logoleiste/logoleiste.css', ['version' => '1.1.0'])
                ->registerAndUseScript('mod_schlamp_logoleiste', 'mod_schlamp_logoleiste/logoleiste.js', ['version' => '1.1.0'], ['defer' => true]);
        }

        return $data;
    }
}
