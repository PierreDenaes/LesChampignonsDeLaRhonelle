<?php

namespace App\Service;

use App\Entity\Profile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class AvatarService
{
    private $vichUploader;
    private $imageManager;

    public function __construct(PropertyMappingFactory $vichUploader)
    {
        $this->vichUploader = $vichUploader;
        $this->imageManager = new ImageManager(new Driver()); // Utilisation du driver Gd
    }

    public function handleAvatarUpload(Profile $profile)
    {
        $avatarFile = $profile->getAvatarFile();
        if (!$avatarFile) {
            return;
        }
        // Vérifiez si l'avatar actuel est le fichier par défaut
        if ($profile->getAvatarName() === Profile::DEFAULT_AVATAR) {
            return;
        }
        
        $originalExtension = $avatarFile->guessExtension();
        $image = $this->imageManager->read($avatarFile->getPathname());
        $encodedImage = $image->encode(new WebpEncoder(), 80);

        $uploadDir = $this->getUploadDirectory($profile, 'avatarFile');
        $this->createDirectories($uploadDir);

        $filename = $this->getFileName($profile, 'avatarFile');
        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $newFileName = $filenameWithoutExtension . '.' . $originalExtension;
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

        // Mise à jour du nom de fichier dans le profil
        $profile->setAvatarName($webpName);
    }
    

    public function handleAvatarRemoval(Profile $profile)
    {
        $avatarName = $profile->getAvatarName();
        if ($avatarName && $avatarName !== Profile::DEFAULT_AVATAR) {
            $uploadDir = $this->getUploadDirectory($profile, 'avatarFile');
            $this->removeFile($uploadDir, $avatarName);
        }
    }
    
    public function removeOldAvatar(string $oldAvatarName, Profile $profile)
    {
        $uploadDir = $this->getUploadDirectory($profile, 'avatarFile');
        $this->removeFile($uploadDir, $oldAvatarName);
    }

    private function getUploadDirectory(Profile $profile, string $field): string
    {
        $mapping = $this->vichUploader->fromField($profile, $field);
        return $mapping->getUploadDestination();
    }

    private function getFileName(Profile $profile, string $field): string
    {
        $mapping = $this->vichUploader->fromField($profile, $field);
        return $mapping->getFileName($profile);
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