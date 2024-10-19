<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
#[Vich\Uploadable]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'gallery', fileNameProperty: 'imageGalName')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger un fichier JPEG, PNG, GIF ou WebP valide.',
    )]
    private ?File $imageGalFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageGalName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le titre ne doit pas être vide.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50 , nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'La catégorie ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageGalFile(?File $imageGalFile = null): void
    {
        $this->imageGalFile = $imageGalFile;
        if (null !== $imageGalFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    
    public function getImageGalFile(): ?File
    {
        return $this->imageGalFile;
    }

    public function setImageGalName(?string $imageGalName): void
    {
        $this->imageGalName = $imageGalName;
    }

    public function getImageGalName(): ?string
    {
        return $this->imageGalName;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }


}
