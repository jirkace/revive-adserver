<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

require_once RV_PATH . '/lib/RV.php';

require_once MAX_PATH . '/lib/OA.php';
require_once LIB_PATH . '/Extension/bannerTypeHtml/bannerTypeHtml.php';
require_once MAX_PATH . '/lib/max/Plugin/Common.php';

/**
 *
 * @package    OpenXPlugin
 * @subpackage Plugins_BannerTypes
 * @abstract
 */
class Plugins_BannerTypeHTML_openXHtmlAdsense_adsense extends Plugins_BannerTypeHTML
{
    /**
     * Return type of plugin
     *
     * @return string A string describing the type of plugin.
     */
    public function getOptionDescription()
    {
        return $this->translate("Google Adsense HTML Banner");
    }

    /**
     * Append type-specific form elements to the base form
     *
     * @param object form
     * @param integer banner id
     */
    public function buildForm(&$form, &$row)
    {
        $form->addElement('hidden', 'ext_bannertype', $this->getComponentIdentifier());

        $bannerId = $row['bannerid'];
        if ($bannerId) {
            $doBanners = OA_Dal::factoryDO('banners_ox_adsense');
            $doBanners->bannerid = $bannerId;
            $doBanners->find(true);
            $row['gas_publisher_id'] = $doBanners->gas_publisher_id;
            $row['gas_ad_type'] = $doBanners->gas_ad_type;
            $row['gas_ad_subtype'] = $doBanners->gas_ad_subtype;
            if ($row['gas_ad_type'] && $row['gas_ad_subtype']) {
                $selected = $row['gas_ad_type'] . '.' . $row['gas_ad_subtype'] . '.' . $row['width'] . '.' . $row['height'];
            }
        }
        $form->setAttribute("onSubmit", "return max_formValidateHtml(this.banner)");
        $header = $form->createElement('header', 'header_html', $GLOBALS['strHTMLBanner'] . " -  banner code");
        $header->setAttribute('icon', 'icon-banner-html.gif');
        $form->addElement($header);

        $form->addElement('text', 'gas_publisher_id', $this->translate("Google Adsense Publisher Id"), $GLOBALS['strHTMLBanner']);

        $form->addElement('header', 'header_b_display', $this->translate("Banner display"));

        $adTypeList['text.button.125.125'] = $this->translate("Text Button 125x125");
        $adTypeList['text.banner.468.60'] = $this->translate("Text Banner 468x60");
        $adTypeList['text.halfbanner.234.60'] = $this->translate("Text Half Banner 234x60");
        $adTypeList['text.vertbanner.120.240'] = $this->translate("Text Vertical Banner 120x240");
        $adTypeList['text.leaderboard.728.90'] = $this->translate("Text Leaderboard 728x90");
        $adTypeList['text.skyscraper.120.600'] = $this->translate("Text Skyscraper 120x600");
        $adTypeList['text.wideskyscraper.160.600'] = $this->translate("Text Wide Skyscraper 160x600");
        $adTypeList['text.smallrectangle.180.150'] = $this->translate("Text Small Rectangle 180x150");
        $adTypeList['text.mediumrectangle.300.250'] = $this->translate("Text Medium Rectangle 300x250");
        $adTypeList['text.largerectangle.336.280'] = $this->translate("Text Large Rectangle 336x280");
        $adTypeList['text.square.250.250'] = $this->translate("Text Square 250x250");
        $adTypeList['text.smallsquare.200.200'] = $this->translate("Text Small Square 200x200");

        $form->addElement('select', 'gas_type', $this->translate("Ad Format"), $adTypeList);

        $form->setDefaults(['gas_type' => $selected]);
    }

    public function validateForm(&$form)
    {
        return true;
        //$oForm->gas_publisher_id
    }

    /**
     * collate the adsense-specific data
     * @todo colours, format
     *
     * @param unknown_type $insert
     * @param unknown_type $bannerid
     * @param unknown_type $aFields
     * @param unknown_type $aVariables
     * @return unknown
     */
    public function preProcessForm($insert, $bannerid, &$aFields, &$aVariables)
    {
        $aAdType = explode('.', $aFields['gas_type']);
        $aFields['gas_ad_type'] = $aAdType[0];
        $aFields['gas_ad_subtype'] = $aAdType[1];
        $aVariables['width'] = $aAdType[2];
        $aVariables['height'] = $aAdType[3];
        $aVariables['htmltemplate'] = $this->buildHtmlTemplate(
            [
                                                                          'gas_publisher_id' => $aFields['gas_publisher_id'],
                                                                          'height' => $aVariables['height'],
                                                                          'width' => $aVariables['width'],
                                                                         ]
        );
        // Attempt to enable click tracking of this banner, this will silently
        // and gracefully fail if the google "3rdpartyserver" isn't installed
        $aVariables['adserver'] = 'google';
        return true;
    }

    public function processForm($insert, $bannerid, &$aFields, &$aVariables)
    {
        $doBanners = OA_Dal::factoryDO('banners_ox_adsense');
        if ($insert) {
            $doBanners->bannerid = $bannerid;
            $doBanners->gas_publisher_id = $aFields['gas_publisher_id'];
            $doBanners->gas_ad_type = $aFields['gas_ad_type'];
            $doBanners->gas_ad_subtype = $aFields['gas_ad_subtype'];
            return $doBanners->insert();
        } else {
            $doBanners->gas_publisher_id = $aFields['gas_publisher_id'];
            $doBanners->gas_ad_type = $aFields['gas_ad_type'];
            $doBanners->gas_ad_subtype = $aFields['gas_ad_subtype'];
            $doBanners->whereAdd('bannerid=' . $bannerid, 'AND');
            return $doBanners->update(DB_DATAOBJECT_WHEREADD_ONLY);
        }
    }

    public function buildHtmlTemplate($aFields)
    {
        $adFormat = $aFields['width'] . 'x' . $aFields['height'] . '_as';
        $result = '<script type="text/javascript"><!--// <![CDATA[
/* openads={url_prefix} bannerid={bannerid} zoneid={zoneid} source={source} */
// ]]> --></script><script type="text/javascript"><!--
google_ad_client = "' . $aFields['gas_publisher_id'] . '";
google_ad_width = ' . $aFields['width'] . ';
google_ad_height = ' . $aFields['height'] . ';
google_ad_format = "' . $adFormat . '";
google_ad_channel ="";
google_color_border = "0066cc";
google_color_bg = "FFFFFF";
google_color_link = "000000";
google_color_url = "666666";
google_color_text = "333333";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>';
        return $result;
    }

    public function selectAllAdsenseBanners()
    {

/*SELECT b.bannerid, b.subtype, b.description, b.width, b.height, g.gas_publisher_id, g.gas_ad_type, g.gas_ad_subtype, b.htmltemplate
FROM ox_banners b
LEFT JOIN ox_banners_ox_adsense g ON  g.bannerid = b.bannerid*/
    }
}
