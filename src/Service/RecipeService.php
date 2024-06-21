<?php

namespace App\Service;

use App\Entity\Recipe;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class RecipeService
{
    private $vichUploader;
    private $imageManager;

    public function __construct(PropertyMappingFactory $vichUploader)
    {
        $this->vichUploader = $vichUploader;
        $this->imageManager = new ImageManager(new Driver()); // Utilisation du driver Gd
    }

    public function handleImageUpload(Recipe $recipe)
    {
        $imageFile = $recipe->getImageFile();
        if (!$imageFile) {
            return;
        }

         // Vérifiez si l'avatar actuel est le fichier par défaut
         if ($recipe->getImageName() === Recipe::DEFAULT_IMAGE) {
            return;
        }
        

        $originalExtension = $imageFile->guessExtension();
        $image = $this->imageManager->read($imageFile->getPathname());
        $encodedImage = $image->encode(new WebpEncoder(), 80);

        $uploadDir = $this->getUploadDirectory($recipe, 'imageFile');
        $this->createDirectories($uploadDir);

        $filename = $this->getFileName($recipe, 'imageFile');
        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $webpName = $filenameWithoutExtension . '.webp';
        $encodedImage->save($uploadDir . '/' . $webpName);

        $sizes = [
            ['width' => 640, 'dir' => '640'],
            ['width' => 320, 'dir' => '320'],
            ['width' => 160, 'dir' => '160'],
        ];

        foreach ($sizes as $size) {
            $resizedImage = $image->resize($size['width'], null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $encodedResizedImage = $resizedImage->encode(new WebpEncoder(), 80);
            $this->createDirectories($uploadDir . '/' . $size['dir']);
            $encodedResizedImage->save($uploadDir . '/' . $size['dir'] . '/' . $webpName);
        }

        // Mise à jour du nom de fichier dans la recette
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
        return $mapping->getFileName($recipe);
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
            // Supprimer également le fichier original
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