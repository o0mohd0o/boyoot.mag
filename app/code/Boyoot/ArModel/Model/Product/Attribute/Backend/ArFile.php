<?php
namespace Boyoot\ArModel\Model\Product\Attribute\Backend;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

class ArFile extends AbstractBackend
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->logger = $logger;
    }

    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attributeCode);

        error_log('ArFile beforeSave - Processing attribute: ' . $attributeCode);
        error_log('ArFile beforeSave - Value type: ' . gettype($value));
        if (is_array($value)) {
            error_log('ArFile beforeSave - Value contents: ' . print_r($value, true));
        }

        if ($value && isset($value[0]['tmp_name'])) {
            try {
                $path = $this->filesystem->getDirectoryRead(
                    DirectoryList::MEDIA
                )->getAbsolutePath(
                    'ar_models/'
                );
                
                error_log('ArFile beforeSave - Upload path: ' . $path);

                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader = $this->uploaderFactory->create(['fileId' => $value]);
                
                error_log('ArFile beforeSave - Uploader created');
                
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                
                // Set allowed extensions based on attribute
                if ($attributeCode === 'ar_model_ios') {
                    $uploader->setAllowedExtensions(['usdz']);
                } else {
                    $uploader->setAllowedExtensions(['glb', 'gltf']);
                }
                
                error_log('ArFile beforeSave - About to save file');
                $result = $uploader->save($path);
                error_log('ArFile beforeSave - Save result: ' . print_r($result, true));

                if ($result['file']) {
                    $object->setData($attributeCode, 'ar_models/' . $result['file']);
                    $object->setData($attributeCode . '_label', $result['file']);
                    error_log('ArFile beforeSave - File saved successfully: ' . $result['file']);
                }
            } catch (\Exception $e) {
                error_log('ArFile beforeSave - Error: ' . $e->getMessage());
                error_log('ArFile beforeSave - Stack trace: ' . $e->getTraceAsString());
                if ($e->getCode() != Uploader::TMP_NAME_EMPTY) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Something went wrong while saving the file.'));
                }
            }
        } else {
            error_log('ArFile beforeSave - No file uploaded or invalid value format');
        }
        
        return parent::beforeSave($object);
    }

    public function afterLoad($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attributeCode);

        error_log('ArFile afterLoad - Loading attribute: ' . $attributeCode);
        error_log('ArFile afterLoad - Current value: ' . $value);

        if ($value) {
            $object->setData($attributeCode . '_label', basename($value));
            error_log('ArFile afterLoad - Set label: ' . basename($value));
        }

        return parent::afterLoad($object);
    }
}
