<?php
namespace Boyoot\ArModel\Controller\Adminhtml\Upload;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Save extends Action
{
    protected $uploaderFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            $fileUploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $fileUploader->setAllowedExtensions(['glb', 'gltf', 'usdz']);
            $fileUploader->setAllowRenameFiles(true);
            $fileUploader->setFilesDispersion(true);

            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $destinationPath = $mediaDirectory->getAbsolutePath('ar_models');

            $uploadResult = $fileUploader->save($destinationPath);

            return $result->setData([
                'name' => $uploadResult['file'],
                'url' => $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) 
                    . 'ar_models' . $uploadResult['file']
            ]);
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
}
