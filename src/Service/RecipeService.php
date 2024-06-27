<?php

namespace App\Service;

use App\Entity\Recipe;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Psr\Log\LoggerInterface;

class RecipeService
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

    public function handleImageUpload(Recipe $recipe)
    {
        $imageFile = $recipe->getImageFile();
        if (!$imageFile) {
            return;
        }

        $originalExtension = $imageFile->guessExtension();
        $image = $this->imageManager->read($imageFile->getPathname());
        $encodedImage = $image->encode(new WebpEncoder(), 80);

        $uploadDir = $this->getUploadDirectory($recipe, 'imageFile');
        $this->createDirectories($uploadDir);

        try {
            $filename = $this->getFileName($recipe, 'imageFile');
        } catch (\RuntimeException $e) {
            $this->logger->error('Error getting file name for recipe image: ' . $e->getMessage());
            return;
        }

        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $webpName = $filenameWithoutExtension . '.webp';
        $encodedImage->save($uploadDir . '/' . $webpName);

        $sizes = [
            ['width' => 640, 'dir' => '640'],
            ['width' => 320, 'dir' => '320'],
            ['width' => 160, 'dir' => '160'],
        ];

        foreach ($sizes as $size) {
            $resizedImage = $image->scale(width: $size['width']);
            $encodedResizedImage = $resizedImage->encode(new WebpEncoder(), 80);
            $this->createDirectories($uploadDir . '/' . $size['dir']);
            $encodedResizedImage->save($uploadDir . '/' . $size['dir'] . '/' . $webpName);
        }

        $recipe->setImageName($webpName);
    }

    public function handleImageRemoval(Recipe $recipe)
    {
        $imageName = $recipe->getImageName();
        if ($imageName) {
            $uploadDir = $this->getUploadDirectory($recipe, 'imageFile');
            $this->removeFile($uploadDir, $imageName);
        }
    }

    public function removeOldImage(string $oldImageName, Recipe $recipe)
    {
        $uploadDir = $this->getUploadDirectory($recipe, 'imageFile');
        $this->removeFile($uploadDir, $oldImageName);
    }

    private function getUploadDirectory(Recipe $recipe, string $field): string
    {
        $mapping = $this->vichUploader->fromField($recipe, $field);
        return $mapping->getUploadDestination();
    }

    private function getFileName(Recipe $recipe, string $field): string
    {
        $mapping = $this->vichUploader->fromField($recipe, $field);
        $fileName = $mapping->getFileName($recipe);

        if ($fileName === null) {
            throw new \RuntimeException('File name is null. Ensure the file is properly uploaded.');
        }

        return $fileName;
    }

    private function removeFile(string $directory, string $filename): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($extensions as $extension) {
            $sizes = ['', '160/', '320/', '640/'];
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