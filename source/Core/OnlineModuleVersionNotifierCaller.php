<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Class makes call to given URL address and sends request parameter.
 *
 * The Online Module Version Notification is used for checking if newer versions of modules are available.
 * Will be used by the upcoming online one click installer.
 * Is still under development - still changes at the remote server are necessary - therefore ignoring the results for now
 *
 * @internal Do not make a module extension for this class.
 * @see      https://oxidforge.org/en/core-oxid-eshop-classes-must-not-be-extended.html
 *
 * @ignore   This class will not be included in documentation.
 */
class OnlineModuleVersionNotifierCaller extends \OxidEsales\Eshop\Core\OnlineCaller
{
    /** Online Module Version Notifier web service url. */
    const WEB_SERVICE_URL = 'https://omvn.oxid-esales.com/check.php';

    /** XML document tag name. */
    const XML_DOCUMENT_NAME = 'omvnRequest';

    /**
     * Performs Web service request
     *
     * @param \OxidEsales\Eshop\Core\OnlineModulesNotifierRequest $oRequest Object with request parameters
     */
    public function doRequest(\OxidEsales\Eshop\Core\OnlineModulesNotifierRequest $oRequest)
    {
        $this->call($oRequest);
    }
    /**
     * @deprecated use self::getXMLDocumentName instead
     */
    protected function _getXMLDocumentName() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getXMLDocumentName();
    }

    /**
     * Gets XML document name.
     *
     * @return string XML document tag name.
     */
    protected function getXMLDocumentName()
    {
        return self::XML_DOCUMENT_NAME;
    }
    /**
     * @deprecated use self::getServiceUrl instead
     */
    protected function _getServiceUrl() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getServiceUrl();
    }

    /**
     * Gets service url.
     *
     * @return string Web service url.
     */
    protected function getServiceUrl()
    {
        return self::WEB_SERVICE_URL;
    }
}
