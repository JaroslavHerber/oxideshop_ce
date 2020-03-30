<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Setup;

use OxidEsales\EshopCommunity\Setup\Exception\SetupControllerExitException;

/**
 * Chooses and executes controller action which must be executec to render expected view
 */
class Dispatcher extends Core
{
    /**
     * Executes current controller action
     */
    public function run()
    {
        // choosing which controller action must be executed
        $sAction = $this->_chooseCurrentAction();

        // executing action which returns name of template to render
        /** @var Controller $oController */
        $oController = $this->getInstance("Controller");

        $view = $oController->getView();
        $view->sendHeaders();

        try {
            $oController->$sAction();
        } catch (SetupControllerExitException $exception) {
        } finally {
            $view->display();
        }
    }
    /**
     * @deprecated use self::chooseCurrentAction instead
     */
    protected function _chooseCurrentAction() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->chooseCurrentAction();
    }

    /**
     * Returns name of controller action script to perform
     *
     * @return string | null
     */
    protected function chooseCurrentAction()
    {
        /** @var Setup $oSetup */
        $oSetup = $this->getInstance("Setup");
        $iCurrStep = $oSetup->getCurrentStep();

        $sName = null;
        foreach ($oSetup->getSteps() as $sStepName => $sStepId) {
            if ($sStepId == $iCurrStep) {
                $sActionName = str_ireplace("step_", "", $sStepName);
                $sName = str_replace("_", "", $sActionName);
                break;
            }
        }

        return $sName;
    }
}
