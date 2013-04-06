<?php
class dsb_ide_helper extends oxAdminView
{
    /**
     * @var string
     */
    protected $_tpl = 'dsb_ide_helper.tpl';

    /**
     * @var array
     */
    protected $_aModuleClasses;

    /**
     * Render method
     *
     * @return string
     */
    public function render()
    {
        $oConfig = $this->getConfig();
        $delete  = $oConfig->getRequestParameter('delete') == null ? false : true;
        $create  = $oConfig->getRequestParameter('create') == null ? false : true;

        if ($delete) {
            $this->deleteParentClassFiles();
        } elseif ($create) {
            $this->deleteParentClassFiles();
            $this->createParentClassFiles();
        }

        $this->checkParentClassFiles();

        return $this->_tpl;
    }

    /**
     * Check if parent class files do exist
     *
     * @return void
     */
    protected function checkParentClassFiles()
    {
        $aClasses = $this->getModuleClassesArray();
        $aErrors  = $aSuccess = array();
        foreach ($aClasses as $aClass) {
            if (file_exists($aClass['fileName'])) {
                $aSuccess[] = $aClass['fileName'];
            } else {
                $aErrors[] = $aClass['fileName'];
            }
        }
        $this->_aViewData['existsSuccess'] = $aSuccess;
        $this->_aViewData['existsErrors']  = $aErrors;
    }

    /**
     * Delete parent class files
     *
     * @return void
     */
    protected function deleteParentClassFiles()
    {
        $aClasses = $this->getModuleClassesArray(false);
        $aErrors  = $aSuccess = array();
        foreach ($aClasses as $aClass) {
            if (file_exists($aClass['fileName'])) {
                if (@unlink($aClass['fileName'])) {
                    $aSuccess[] = $aClass['fileName'];
                } else {
                    $aErrors[] = $aClass['fileName'];
                }
            }
        }
        $this->_aViewData['deleteSuccess'] = $aSuccess;
        $this->_aViewData['deleteErrors']  = $aErrors;
    }

    /**
     * Create parent class files
     *
     * @return void
     */
    protected function createParentClassFiles()
    {
        $aClasses = $this->getModuleClassesArray();
        $aErrors  = $aSuccess = array();
        foreach ($aClasses as $aClass) {
            $blResult = file_put_contents($aClass['fileName'], $aClass['content']);
            if ($blResult !== false) {
                $aSuccess[] = $aClass['fileName'];
            } else {
                $aErrors[] = $aClass['fileName'];
            }
        }
        $this->_aViewData['createSuccess'] = $aSuccess;
        $this->_aViewData['createErrors']  = $aErrors;
    }

    /**
     * Build array containing the filename with absolute path and the content for each parent class
     *
     * @param bool $blRemoveDisabledModules Whether to exclude disabled modules or not
     *
     * @return array
     */
    protected function getModuleClassesArray($blRemoveDisabledModules = true)
    {
        if (null != $this->_aModuleClasses) {
            return $this->_aModuleClasses;
        }

        /**
         * @var oxModule $oModule
         */
        $oModule          = oxNew('oxmodule');
        $aModules         = $oModule->getAllModules();
        $aDisabledModules = $oModule->getDisabledModules();
        $modulePath       = $this->getViewConfig()->getModulePath('dsb_ide_helper');
        $moduleBasePath   = str_replace('dsb_ide_helper/', '', $modulePath);
        $aClasses         = array();
        foreach ($aModules as $sClassName => $aModuleClasses) {
            $sParentClassName = $sClassName;
            foreach ($aModuleClasses as $sModuleClass) {
                if ($blRemoveDisabledModules && in_array($sModuleClass, $aDisabledModules)) {
                    continue;
                }

                $aDirectories     = explode('/', $sModuleClass);
                $sModuleClassName = $aDirectories[count($aDirectories) - 1] . '_parent';
                unset($aDirectories[count($aDirectories) - 1]);

                $aClasses[] = array(
                    'fileName' => $moduleBasePath . implode('/', $aDirectories) . '/' . $sModuleClassName . '.php',
                    'content'  => $this->getFileContent($sModuleClassName, $sParentClassName),
                );

                $sParentClassName = $sModuleClassName;
            }
        }
        $this->_aModuleClasses = $aClasses;

        return $aClasses;
    }

    /**
     * Get parent class file content
     *
     * @param string $className       Name of class
     * @param string $parentClassName Name of parent class
     *
     * @return string
     */
    protected function getFileContent($className, $parentClassName)
    {
        $tpl = "<?php\n"
               . "/**\n"
               . " * Auto generated parent class file for auto completion in IDE's\n"
               . " * Generated by module dsb_ide_helper\n"
               . " */\n"
               . "class %s extends %s {}\n";

        $ret = sprintf($tpl, $className, $parentClassName);

        return $ret;
    }
}