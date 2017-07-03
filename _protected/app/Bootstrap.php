<?php
/**
 * @title          Bootstrap
 *
 * @author         Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright      (c) 2012-2017, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @link           http://ph7cms.com
 * @package        PH7 / App / Core
 * @version        2.0
 */

namespace PH7;

defined('PH7') or exit('Restricted access');

use PH7\App\Includes\Classes\Loader\Autoloader as AppLoader;
use PH7\Framework\Config\Config;
use PH7\Framework\Core\Kernel;
use PH7\Framework\Error\CException as Except;
use PH7\Framework\File\Import;
use PH7\Framework\Loader\Autoloader as FrameworkLoader;
use PH7\Framework\Mvc\Router\FrontController;
use PH7\Framework\Navigation\Browser;
use PH7\Framework\Registry\Registry;
use PH7\Framework\Server\Server;

/*** Begin Loading Files ***/
require 'configs/constants.php';
require 'includes/helpers/misc.php';

class Bootstrap
{
    /**
     * @staticvar object $oInstance
     */
    private static $oInstance = null;

    /**
     * Set constructor/cloning to private since it's a singleton class.
     */
    private function __construct() {}
    private function __clone() {}

    /**
     * Get instance of class.
     *
     * @return Bootstrap Returns the instance class or create initial instance of the class.
     */
    public static function getInstance()
    {
        return (null === static::$oInstance) ? static::$oInstance = new static : static::$oInstance;
    }

    /**
     * Set a default timezone if it is not already configured in environment.
     *
     * @return void
     */
    public function setTimezoneIfNotSet()
    {
        if (!ini_get('date.timezone'))
            ini_set('date.timezone', PH7_DEFAULT_TIMEZONE);
    }

    /**
     * zlib-compressed output.
     *
     * These "zlib output compression" compress the pages.
     * It save your bandwidth and gives faster download of the pages.
     * WARNING: It can consume high CPU resources on the server.
     * So it might be wise not to use this method if the server isn't so powerful.
     *
     * @return void
     */
    public function zlipCompression()
    {
        ini_set('zlib.output_compression', 2048);
        ini_set('zlib.output_compression_level', 6);
    }

    /**
     * Initialize the app, load the files and launch the main FrontController router.
     *
     * @return void
     *
     * @throws Except\UserException
     * @throws Except\PH7Exception
     * @throws \Exception
     */
    public function run()
    {
        try {
            $this->loadInitFiles();

            //** Temporary code. In the near future, pH7CMS will be usable without mod_rewrite
            if (!Server::isRewriteMod()) {
                $this->notRewriteModEnabledError();
                exit;
            }  //*/

            // Enable client browser cache
            (new Browser)->cache();

            new Server; // Start Server

            Registry::getInstance()->start_time = microtime(true);

            /**
             * Initialize the FrontController, we are asking the front controller to process the HTTP request
             */
            FrontController::getInstance()->runRouter();
        } # \PH7\Framework\Error\CException\UserException
        catch (Except\UserException $oE) {
            echo $oE->getMessage(); // Simple User Error with Exception
        } # \PH7\Framework\Error\CException\PH7Exception
        catch (Except\PH7Exception $oE) {
            Except\PH7Exception::launch($oE);
        } # \Exception and other...
        catch (\Exception $oE) {
            Except\PH7Exception::launch($oE);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }

    /**
     * Load all necessary files for running the app.
     *
     * @return void
     */
    private function loadInitFiles()
    {
        // Loading Framework Classes
        require PH7_PATH_FRAMEWORK . 'Loader/Autoloader.php';
        FrameworkLoader::getInstance()->init();

        /** Loading configuration files environments **/
        // For All environment
        Import::file(PH7_PATH_APP . 'configs/environment/all.env');
        // Specific to the current environment
        Import::file(PH7_PATH_APP . 'configs/environment/' . Config::getInstance()->values['mode']['environment'] . '.env');

        // Loading Class ~/protected/app/includes/classes/*
        Import::pH7App('includes.classes.Loader.Autoloader');
        AppLoader::getInstance()->init();

        // Loading Debug class
        Import::pH7FwkClass('Error.Debug');

        // Loading String Class
        Import::pH7FwkClass('Str.Str');

        /* Structure/General.class.php functions are not currently used */
        // Import::pH7FwkClass('Structure.General');
    }

    /**
     * Display an error message if the Apache mod_rewrite is not enabled.
     *
     * @return void HTML output.
     */
    private function notRewriteModEnabledError()
    {
        $sMsg = '<p class="warning"><a href="' . Kernel::SOFTWARE_WEBSITE . '">pH7CMS</a> requires Apache "mod_rewrite".</p>
        <p>Firstly, please <strong>make sure the ".htaccess" file has been uploaded to the root directory where pH7CMS is installed</strong>. If not, use your FTP client (such as Filezilla) and upload it again from pH7CMS unziped package and try again.<br />
        Secondly, please <strong>make sure "mod_rewrite" is correctly installed</strong>.<br /> Click <a href="http://ph7cms.com/doc/en/how-to-install-rewrite-module" target="_blank">here</a> if you want to get more information on how to install the rewrite module.<br /><br />
        After that, please <a href="' . PH7_URL_ROOT . '">retry</a>.</p>';

        echo html_body("Apache's mod_rewrite is required", $sMsg);
    }

    private function __clone()
    {
    }
}