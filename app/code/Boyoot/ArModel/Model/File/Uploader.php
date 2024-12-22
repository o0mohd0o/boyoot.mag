<?php
namespace Boyoot\ArModel\Model\File;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Helper\File\Storage;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Psr\Log\LoggerInterface;

class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $fileId
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        $fileId,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($fileId);
    }

    /**
     * Set allowed extensions
     *
     * @param string|array $extensions
     * @return $this
     */
    public function setAllowedExtensions($extensions = [])
    {
        if (is_string($extensions)) {
            $extensions = explode(',', $extensions);
        }
        $extensions = array_map('trim', $extensions);
        $extensions = array_map('strtolower', $extensions);
        $extensions = array_filter($extensions);

        $this->logger->info('Setting allowed extensions:', ['extensions' => $extensions]);
        $this->_allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Save file to storage
     *
     * @param string $destinationFolder
     * @param string|null $newFileName
     * @return array
     * @throws LocalizedException
     */
    public function save($destinationFolder, $newFileName = null)
    {
        try {
            if ($this->_file === null) {
                throw new LocalizedException(__('No file information available'));
            }

            // Check file extension
            $fileExtension = strtolower(pathinfo($this->_file['name'], PATHINFO_EXTENSION));
            if (!$this->checkAllowedExtension($fileExtension)) {
                throw new LocalizedException(
                    __('File extension "%1" is not allowed. Allowed extensions: %2', 
                    $fileExtension, 
                    implode(', ', $this->_allowedExtensions))
                );
            }

            // Ensure destination folder exists
            if (!is_dir($destinationFolder)) {
                if (!mkdir($destinationFolder, 0755, true)) {
                    throw new LocalizedException(__('Failed to create destination folder'));
                }
            }

            // Check if source file exists and is readable
            if (!is_file($this->_file['tmp_name']) || !is_readable($this->_file['tmp_name'])) {
                throw new LocalizedException(__('Source file is not readable or does not exist'));
            }

            // Call parent save method
            $result = parent::save($destinationFolder, $newFileName);
            if (!$result) {
                throw new LocalizedException(__('Failed to save file'));
            }

            // Verify file exists at destination
            $destinationFile = $destinationFolder . '/' . $result['file'];
            if (!file_exists($destinationFile)) {
                throw new LocalizedException(__('File not found at destination after save'));
            }

            // Set proper file permissions
            chmod($destinationFile, 0644);

            return $result;

        } catch (LocalizedException $e) {
            $this->logger->error('LocalizedException during save: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Exception during save: ' . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * Check if specified extension is allowed
     *
     * @param string $extension
     * @return boolean
     */
    public function checkAllowedExtension($extension)
    {
        if (empty($this->_allowedExtensions)) {
            $this->logger->info('No extensions restrictions');
            return true;
        }

        // Convert to lowercase
        $extension = strtolower($extension);
        $allowedLower = array_map('strtolower', $this->_allowedExtensions);

        // Check if extension is in array
        $result = in_array($extension, $allowedLower);
        $this->logger->info('Extension check result: ' . ($result ? 'true' : 'false'));
        
        return $result;
    }
}
