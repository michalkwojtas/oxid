<?php

class Styla_Setup
{
    // Default values
    const STYLA_FEED_BASEDIR = 'stylafeed';
    const STYLA_MAGAZINE_BASEDIR = 'magazin';

    // URLs to write to oxseo
    private static $_aFeedUrls = array(
        array('orig_url' => 'index.php?cl=Styla_Feed&fnc=showAll', 'seo_action' => 'index/'),
        array('orig_url' => 'index.php?cl=Styla_Feed&fnc=showCategories', 'seo_action' => 'index/category/'),
        array('orig_url' => 'index.php?cl=Styla_Feed&fnc=showProduct', 'seo_action' => 'index/product/'),
    );

    protected static $_aMagazineUrls = array(
        array('orig_url' => 'index.php?cl=Styla_Magazine', 'seo_action' => ''),
        array('orig_url' => 'index.php?cl=Styla_Magazine&fnc=getPluginVersion', 'seo_action' => 'version/'),
    );

    /**
     * Updates SEO URLs for Styla feed and magazine
     */
    public static function updateSeoUrls()
    {
        self::cleanup(); // prevent duplicate entries

        // Writing magazine URLs to SEO
        $sBaseDir = oxRegistry::getConfig()->getConfigParam('styla_seo_basedir');
        if ($sBaseDir == '') {
            $sBaseDir = self::STYLA_MAGAZINE_BASEDIR;
        }

        self::_writeSeoUrls($sBaseDir, self::$_aMagazineUrls);

        // Writing feed URLs to SEO (only if product API is enabled)
        if (oxRegistry::getConfig()->getConfigParam('styla_api_active')) {
            $sBaseDir = oxRegistry::getConfig()->getConfigParam('styla_feed_basedir');
            if ($sBaseDir == '') {
                $sBaseDir = self::STYLA_FEED_BASEDIR;
            }

            self::_writeSeoUrls($sBaseDir, self::$_aFeedUrls);
        }
    }

    /**
     * Writes SEO URLs with given base directory to oxseo
     *
     * @param string $sBaseDir
     * @param array  $aUrls
     */
    protected static function _writeSeoUrls($sBaseDir, $aUrls)
    {
        $sBaseDir = rtrim($sBaseDir, '/') . '/';
        $iShopID = oxRegistry::getConfig()->getShopId();
        $aLanguages = oxRegistry::getLang()->getLanguageArray();
        $defaultLang = oxRegistry::getConfig()->getConfigParam('sDefaultLang');

        foreach ($aUrls as $aURL) {
            foreach ($aLanguages as $oLang) {
                $sOxID = oxRegistry::get('oxUtilsObject')->generateUId();
                $sLangPrefix = '';
                if ($oLang->id != $defaultLang) {
                    $sLangPrefix = $oLang->abbr . '/';
                }
                $sURL = $sLangPrefix . $sBaseDir . $aURL['seo_action'];

                $sQuery = "INSERT INTO `oxseo` (`OXOBJECTID`, `OXIDENT`, `OXSHOPID`, `OXLANG`, `OXSTDURL`, `OXSEOURL`, `OXTYPE`, `OXFIXED`, `OXEXPIRED`, `OXPARAMS`) VALUES
                  ('" . $sOxID . "', '" . md5(strtolower($sURL)) . "', '" . $iShopID . "', $oLang->id, '" . $aURL['orig_url'] . "', '" . $sURL . "', 'static', 0, 0, '');";
                oxDb::getDb()->Execute($sQuery);
            }
        }
    }

    /**
     * Removes old oxseo entries for Styla modules
     */
    public static function cleanup()
    {
        $oDb = oxDb::getDb();
        $sShopId = oxRegistry::getConfig()->getShopId();

        // Delete legacy settings, they are otherwise conflicting with the new module's settings
        $sQuery = "DELETE FROM oxconfig WHERE (OXMODULE = 'module:StylaSEO' or OXMODULE = 'module:StylaFeed') AND OXSHOPID = ?";
        $oDb->Execute($sQuery, array($sShopId));

        $sQuery = "DELETE FROM `oxseo` WHERE `OXSTDURL` LIKE '%Styla_%' and oxshopid = ?";
        $oDb->Execute($sQuery, array($sShopId));

        // Legacy: Old StylaFeed module
        $sQuery = "DELETE FROM `oxseo` WHERE `OXSTDURL` LIKE '%StylaFeed_Output%' and oxshopid = ?";
        $oDb->Execute($sQuery, array($sShopId));

        // Legacy: Old StylaSEO module
        $sQuery = "DELETE FROM `oxseo` WHERE `OXSTDURL` LIKE '%StylaSEO_Output%' and oxshopid = ?";
        $oDb->Execute($sQuery, array($sShopId));

        // Legacy
        $sQuery = "DELETE FROM `oxseo` WHERE `OXSTDURL` LIKE '%Amazinefeed_Output%' and oxshopid = ?";
        $oDb->Execute($sQuery, array($sShopId));
    }

    /**
     * Called when activating the module
     */
    static function install()
    {
        self::updateSeoUrls();
    }

    /**
     * Called when deactivating the module
     */
    static function uninstall()
    {
        self::cleanup();
    }
}
