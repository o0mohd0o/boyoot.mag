<?php
namespace Boyoot\ArModel\Controller\Adminhtml\Upload;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ?LoggerInterface $logger = null
    ) {
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->logger = $logger ?? \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($context);
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->getRequest()->isPost()) {
                throw new LocalizedException(__('Wrong request method type'));
            }

            $files = $this->getRequest()->getFiles();
            $this->logger->info('Files data:', ['files' => print_r($files->toArray(), true)]);

            if (empty($files->toArray())) {
                throw new LocalizedException(__('No files were uploaded.'));
            }

            $fileId = key($files->toArray());
            if (empty($fileId)) {
                throw new LocalizedException(__('No file ID found'));
            }

            $this->logger->info('Processing file upload with ID: ' . $fileId);

            // Get media directory
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadPath = 'ar_models';
            $absolutePath = $mediaDirectory->getAbsolutePath($uploadPath);

            $this->logger->info('Upload path details:', [
                'media_dir' => $mediaDirectory->getAbsolutePath(),
                'upload_path' => $uploadPath,
                'absolute_path' => $absolutePath,
                'exists' => is_dir($absolutePath),
                'writable' => is_writable($absolutePath)
            ]);

            // Create directory if it doesn't exist
            if (!$mediaDirectory->isExist($uploadPath)) {
                $this->logger->info('Creating upload directory');
                $mediaDirectory->create($uploadPath);
            }

            // Get uploaded file info
            $fileInfo = $files->toArray()[$fileId];
            $this->logger->info('Uploaded file details:', [
                'name' => $fileInfo['name'],
                'type' => $fileInfo['type'],
                'size' => $fileInfo['size'],
                'tmp_name' => $fileInfo['tmp_name'],
                'tmp_exists' => file_exists($fileInfo['tmp_name']),
                'tmp_readable' => is_readable($fileInfo['tmp_name'])
            ]);

            // Manually move the file
            $destinationFile = $absolutePath . '/' . $fileInfo['name'];
            $this->logger->info('Moving file to: ' . $destinationFile);

            if (!move_uploaded_file($fileInfo['tmp_name'], $destinationFile)) {
                throw new LocalizedException(__('Failed to move uploaded file'));
            }

            // Set proper permissions
            chmod($destinationFile, 0644);

            // Prepare result
            $result = [
                'name' => $fileInfo['name'],
                'type' => $fileInfo['type'],
                'size' => $fileInfo['size'],
                'file' => $fileInfo['name'],
                'url' => $this->storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $uploadPath . '/' . $fileInfo['name']
            ];

            $this->logger->info('File upload successful', ['result' => $result]);
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);

        } catch (LocalizedException $e) {
            $this->logger->error('LocalizedException: ' . $e->getMessage());
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
            $result = ['error' => __('Something went wrong while saving the file.'), 'errorcode' => $e->getCode()];
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
        }
    }

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
}
