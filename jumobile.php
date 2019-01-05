<?php
/**
 * JUMobile
 *
 * @package          Joomla.Site
 * @subpackage       plg_jumobile
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2016-2019 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * @package        Simple Mobile Detection
 * @copyright      Copyright (C) 2013 Conflate. All rights reserved.
 * @license        GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.environment.browser');

require_once __DIR__ . '/lib/Mobile_Detect.php';

/**
 * JUMobile Plugin.
 *
 * @property  _plugin
 * @since  1.0
 */
class plgSystemJUMobile extends JPlugin
{
    protected $app;
    protected $isMobile = false;
    protected $devMode = 0;

    /**
     * plgSystemJUMobile constructor.
     *
     * @param $subject
     * @param $params
     */
    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);

        $this->_plugin = JPluginHelper::getPlugin('system', 'jumobile');
        $this->_params = new JRegistry($this->_plugin->params);
    }

    /**
     *
     * @return bool
     *
     * @throws Exception
     * @since 1.0
     */
    public function onAfterRender()
    {
        $app    = JFactory::getApplication();
        $params = $this->_params;

        if($app->getName() !== 'site')
        {
            return true;
        }

        if(@$_COOKIE[$this->params->get('cookiename')] == '0')
        {
            return true;
        }

        $exclusion = $params->get('exclusion', '');
        if($exclusion != '')
        {
            $urls = explode("\r\n", $exclusion);
            foreach ($urls as $url)
            {
                if(strpos(JURI::current(), $url) !== false)
                {
                    return true;
                }
            }
        }

        $lib_md  = new Mobile_Detect();
        $browser = JBrowser::getInstance();
        $agent   = $browser->getAgentString();

        $enabled = $app->getUserStateFromRequest('jumobile.isenabled', 'jumobile', true, 'bool');

        $this->isMobile = ($lib_md->isMobile() || $lib_md->isTablet());

        if($params->get('devmode', false))
        {
            $ip_addresses = array_filter(explode("\r\n", $params->get('devmodeip', '')));

            if(!empty($ip_addresses))
            {
                foreach ($ip_addresses as $ip)
                {
                    if($this->get_ip() == $ip)
                    {
                        $this->devMode = 1;

                        break;
                    }
                    else
                    {
                        return true;
                    }
                }
            }
        }

        if(($this->devMode == '1' ||
                ($lib_md && $this->isMobile) ||
                ((!$lib_md && $browser->isMobile()) || false !== stripos($agent, 'mobile'))) && $this->params->get('allowcache') == 1 && $enabled
        )
        {
            $app->allowCache(true);
            if($this->params->get('pragma') == 1)
            {
                $app->setHeader('Pragma', 'public', true);
            }

            if($this->params->get('cachecontrol') == 1)
            {
                $app->setHeader('Cache-Control', 'public', true);
            }

            if($this->params->get('expires') == 1)
            {
                $app->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $this->params->get('expirestime')) . ' GMT', true);
            }
        }

        return true;
    }

    /**
     *
     * @return string
     *
     * @since 1.0
     */
    private function get_ip()
    {
        if(!empty(getenv('HTTP_CLIENT_IP')))
        {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        }
        elseif(!empty(getenv('HTTP_X_FORWARDED_FOR')))
        {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif(!empty(getenv('HTTP_X_FORWARDED')))
        {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        }
        elseif(!empty(getenv('HTTP_FORWARDED_FOR')))
        {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        }
        elseif(!empty(getenv('HTTP_FORWARDED')))
        {
            $ipaddress = getenv('HTTP_FORWARDED');
        }
        elseif(!empty(getenv('REMOTE_ADDR')))
        {
            $ipaddress = getenv('REMOTE_ADDR');
        }
        else
        {
            $ipaddress = 'UNKNOWN';
        }

        $ips = explode(',', $ipaddress);

        return trim($ips[0]);
    }

    /**
     *
     * @return bool
     *
     * @throws Exception
     * @since 1.0
     */
    public function onAfterInitialise()
    {
        $app    = JFactory::getApplication();
        $params = $this->_params;

        if($app->getName() !== 'site')
        {
            return true;
        }

        if(@$_COOKIE[$this->params->get('cookiename')] == '0')
        {
            return true;
        }

        $exclusion = $params->get('exclusion', '');
        if($exclusion != '')
        {
            $urls = explode("\r\n", $exclusion);
            foreach ($urls as $url)
            {
                if(strpos(JURI::current(), $url) !== false)
                {
                    return true;
                }
            }
        }

        $lib_md  = new Mobile_Detect();
        $browser = JBrowser::getInstance();
        $agent   = $browser->getAgentString();

        $enabled = $app->getUserStateFromRequest('jumobile.isenabled', 'jumobile', true, 'bool');

        $this->isMobile = ($lib_md->isMobile() || $lib_md->isTablet());

        if($params->get('devmode', false))
        {
            $ip_addresses = array_filter(explode("\r\n", $params->get('devmodeip', '')));

            if(!empty($ip_addresses))
            {
                foreach ($ip_addresses as $ip)
                {
                    if($this->get_ip() == $ip)
                    {
                        $this->devMode = 1;
                        $lib_md        = 1;
                        $app->setUserState('jumobile.ismobile', true);
                        $app->setUserState('jumobile.device', 'mobile');

                        break;
                    }
                    else
                    {
                        return true;
                    }
                }
            }
        }

        if($this->devMode == '1' ||
            ($lib_md && $this->isMobile) ||
            ((!$lib_md && $browser->isMobile()) || false !== stripos($agent, 'mobile')) // stristr($agent, 'mobile')
        )
        {
            if($enabled)
            {
                $redirect     = $params->get('redirectmobile', false);
                $redirectPage = $params->get('redirectpage', false);
                $redirectOnce = $params->get('redirectonce', false);
                $mobiledomain = $params->get('mobiledomain', false);

                $template = $params->get('template', false);

                if($redirect &&
                    (!$app->getUserState('jumobile.isredirected', false) || !$redirectOnce)
                )
                {
                    $uri = JUri::getInstance();
                    if(substr($uri->current(), 0, strlen($mobiledomain)) != $mobiledomain)
                    {
                        $app->setUserState('jumobile.isredirected', true);
                        $page = ltrim($uri->toString(array('path', 'query')), '/');

                        if(preg_match('#^http(?:s)?\:\/\/.*#i', $mobiledomain))
                        {
                            $app->redirect($mobiledomain . ($redirectPage ? '/' . $page : ''));
                        }
                        else
                        {
                            if($uri->getHost() != $mobiledomain)
                            {
                                $url = $uri->getScheme() . '://' . $mobiledomain;
                                $app->redirect($url . ($redirectPage ? '/' . $page : ''));
                            }
                        }
                    }
                }

                if($template > 0 && $template != '-1')
                {
                    $app->input->set('templateStyle', $template);
                }

                $app->setUserState('jumobile.ismobile', true);
            }
            else
            {
                $app->setUserState('jumobile.ismobile', false);
            }

            $device = new JRegistry();
            $device->set('browser', $browser->getBrowser());
            $device->set('platform', $browser->getPlatform());

            $app->setUserState('jumobile.isdevice', true);
            $app->setUserState('jumobile.device', $device->toString());
            $app->setUserState('jumobile.detection.system', $lib_md);
            $app->input->def('jumobile', true);
        }
        else
        {
            $app->setUserState('jumobile.ismobile', false);
            $app->setUserState('jumobile.isdevice', false);
            $app->setUserState('jumobile.device', '{}');
            $app->setUserState('jumobile.detection.system', false);
            $app->input->def('jumobile', false);
        }

        $cachingOn = true;

        if($cachingOn)
        {
            $registeredurlparams           = !empty($app->registeredurlparams) ? $app->registeredurlparams : new stdClass;
            $registeredurlparams->jumobile = 'BOOLEAN';
            $app->registeredurlparams      = $registeredurlparams;
        }

        return true;
    }
}