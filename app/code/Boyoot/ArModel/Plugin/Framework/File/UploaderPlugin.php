<?php
namespace Boyoot\ArModel\Plugin\Framework\File;

use Magento\Framework\File\Uploader;
use Psr\Log\LoggerInterface;

class UploaderPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function beforeCheckAllowedExtension(
        Uploader $subject,
        $extension
    ) {
        error_log('UploaderPlugin - Checking extension: ' . $extension);
        error_log('UploaderPlugin - Current allowed extensions: ' . print_r($subject->_allowedExtensions, true));
        
        $currentAllowed = $subject->_allowedExtensions;
        if (empty($currentAllowed)) {
            $currentAllowed = [];
        }
        
        // Add AR model extensions
        $arExtensions = ['glb', 'gltf', 'usdz'];
        $subject->_allowedExtensions = array_merge($currentAllowed, $arExtensions);
        
        error_log('UploaderPlugin - Modified allowed extensions: ' . print_r($subject->_allowedExtensions, true));
        
        return [$extension];
    }
}
