<?php

namespace AstralWeb\ProductImageFix\Model\Catalog\Product\Gallery;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor as ParentProcessor;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\Uploader;

/**
 * Class Processor
 * @package AstralWeb\ProductImageFix\Model\Catalog\Product\Gallery
 */
class Processor extends ParentProcessor
{
    /**
     * @param Product $product
     * @param string $file
     * @param null $mediaAttribute
     * @param bool $move
     * @param bool $exclude
     * @return mixed|string
     * @throws LocalizedException
     */
    public function addImage(
        Product $product,
        $file,
        $mediaAttribute = null,
        $move = false,
        $exclude = true
    ) {
        $file = $this->mediaDirectory->getRelativePath($file);

        if (!$this->mediaDirectory->isFile($file)) {
            throw new LocalizedException(__('The image does not exist.'));
        }

        $pathinfo = pathinfo($file);

        $imgExtensions = ['jpg', 'jpeg', 'gif', 'png'];

        if (!isset($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), $imgExtensions)) {
            throw new LocalizedException(__('Please correct the image file type.'));
        }

        $fileName = Uploader::getCorrectFileName($pathinfo['basename']);

        $dispretionPath = Uploader::getDispretionPath($fileName);

        $fileName = $dispretionPath . '/' . $fileName;

        $fileName = $this->getNotDuplicatedFilename($fileName, $dispretionPath);

        $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);

        try {
            /** @var $storageHelper Database */
            $storageHelper = $this->fileStorageDb;
            if ($move) {
                $this->mediaDirectory->renameFile($file, $destinationFile);

                //If this is used, filesystem should be configured properly
                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            } else {
                $this->mediaDirectory->copyFile($file, $destinationFile);

                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('We couldn\'t move this file: %1.', $e->getMessage()));
        }

        $fileName = str_replace('\\', '/', $fileName);

        $attrCode = $this->getAttribute()->getAttributeCode();

        $mediaGalleryData = $product->getData($attrCode);

        $position = 0;

        if (!is_array($mediaGalleryData)) {
            $mediaGalleryData = ['images' => []];
        }

        foreach ($mediaGalleryData['images'] as &$image) {
            if (isset($image['position']) && $image['position'] > $position) {
                $position = $image['position'];
            }
        }

        $position++;

        $mediaGalleryData['images'][] = [
            'file' => $fileName,
            'position' => $position,
            'label' => '',
            'disabled' => (int)$exclude,
            'media_type' => 'image',
            'types' => $mediaAttribute
        ];

        $product->setData($attrCode, $mediaGalleryData);

        if ($mediaAttribute !== null) {
            $this->setMediaAttribute($product, $mediaAttribute, $fileName);
        }

        return $fileName;
    }
}
