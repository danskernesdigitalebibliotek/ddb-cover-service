<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VendorRepository")
 */
class Vendor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $class;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", unique=true)
     */
    private $rank;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageServerURI;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dataServerURI;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dataServerUser;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dataServerPassword;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Source", mappedBy="vendor", orphanRemoval=true)
     */
    private $sources;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the id.
     *
     * Id's must be manually set to match the 'VENDOR_ID' of the respective service class.
     *
     * @param int $id
     *
     * @return Vendor
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImageServerURI(): ?string
    {
        return $this->imageServerURI;
    }

    public function setImageServerURI(string $imageServerURI): self
    {
        $this->imageServerURI = $imageServerURI;

        return $this;
    }

    public function getDataServerURI(): ?string
    {
        return $this->dataServerURI;
    }

    public function setDataServerURI(string $dataServerURI): self
    {
        $this->dataServerURI = $dataServerURI;

        return $this;
    }

    public function getDataServerUser(): ?string
    {
        return $this->dataServerUser;
    }

    public function setDataServerUser(string $dataServerUser): self
    {
        $this->dataServerUser = $dataServerUser;

        return $this;
    }

    public function getDataServerPassword(): ?string
    {
        return $this->dataServerPassword;
    }

    public function setDataServerPassword(string $dataServerPassword): self
    {
        $this->dataServerPassword = $dataServerPassword;

        return $this;
    }

    /**
     * @return Collection|Source[]
     */
    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(Source $source): self
    {
        if (!$this->sources->contains($source)) {
            $this->sources[] = $source;
            $source->setVendor($this);
        }

        return $this;
    }

    public function removeSource(Source $source): self
    {
        if ($this->sources->contains($source)) {
            $this->sources->removeElement($source);
            // set the owning side to null (unless already changed)
            if ($source->getVendor() === $this) {
                $source->setVendor(null);
            }
        }

        return $this;
    }
}
