<?php

namespace App\Service;

use App\Entity\Gallery;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Psr\Log\LoggerInterface;

class GalleryService
{
    private $vichUploader;
    private $imageManager;
    private $logger;

    public function __construct(PropertyMappingFactory $vichUploader, LoggerInterface $logger)
    {
        $this->vichUploader = $vichUploader;
        $this->imageManager = new ImageManager(new Driver());
        $this->logger = $logger;
    }

    public function handleImageUpload(Gallery $gallery)
    {
        $imageFile = $gallery->getImageGalFile();
        if (!$imageFile) {
            return;
        }

        $originalExtension = $imageFile->guessExtension();
        $image = $this->imageManager->read($imageFile->getPathname());
        $encodedImage = $image->encode(new WebpEncoder(), 80);

        $uploadDir = $this->getUploadDirectory($gallery, 'imageGalFile');
        $this->createDirectories($uploadDir);

        try {
            $filename = $this->getFileName($gallery, 'imageGalFile');
        } catch (\RuntimeException $e) {
            $this->logger->error('Error getting file name for gallery image: ' . $e->getMessage());
            return;
        }

        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $webpName = $filenameWithoutExtension . '.webp';
        $encodedImage->save($uploadDir . '/' . $webpName);

        $sizes = [
            ['width' => 1200, 'dir' => '1200'],
            ['width' => 640, 'dir' => '640'],
            ['width' => 320, 'dir' => '320'],
            ['width' => 160, 'dir' => '160'],
        ];

        foreach ($sizes as $size) {
            $resizedImage = $image->resize($size['width'], null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $encodedResizedImage = $resizedImage->encode(new WebpEncoder(), 80);
            $this->createDirectories($uploadDir . '/' . $size['dir']);
            $encodedResizedImage->save($uploadDir . '/' . $size['dir'] . '/' . $webpName);
        }

        $gallery->setImageGalName($webpName);
    }

    public function handleImageRemoval(Gallery $gallery)
    {
        $imageName = $gallery->getImageGalName();
        if ($imageName) {
            $uploadDir = $this->getUploadDirectory($gallery, 'imageGalFile');
            $this->removeFile($uploadDir, $imageName);
        }
    }

    public function removeOldImage(string $oldImageName, Gallery $gallery)
    {
        $uploadDir = $this->getUploadDirectory($gallery, 'imageGalFile');
        $this->removeFile($uploadDir, $oldImageName);
    }

    private function getUploadDirectory(Gallery $gallery, string $field): string
    {
        $mapping = $this->vichUploader->fromField($gallery, $field);
        return $mapping->getUploadDestination();
    }

    private function getFileName(Gallery $gallery, string $field): string
    {
        $mapping = $this->vichUploader->fromField($gallery, $field);
        $fileName = $mapping->getFileName($gallery);

        if ($fileName === null) {
            throw new \RuntimeException('File name is null. Ensure the file is properly uploaded.');
        }

        return $fileName;
    }

    private function removeFile(string $directory, string $filename): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($extensions as $extension) {
            $sizes = ['', '160/', '320/', '640/', '1200/'];
            foreach ($sizes as $size) {
                $filePath = $directory . '/' . $size . $filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $originalFilePath = $directory . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
            if (file_exists($originalFilePath)) {
                unlink($originalFilePath);
            }
        }
    }

    private function createDirectories(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}