<?php

namespace Richard87\ApiRoute\Tests\resources\src\Entity;

use Richard87\ApiRoute\Attributes\Rest;
use Richard87\ApiRoute\Attributes\Property;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="messages")
 */
#[Rest\Get]
class Message
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    #[Property(write: false)]
    private ?int $id = null;
    /**
     * @ORM\ManyToOne(targetEntity=User::class,cascade={"persist"}, inversedBy="messages")
     */
    #[Property(write: false)]
    private User $sender;
    /**
     * @ORM\Column(type="datetime")
     */
    #[Property(write: false)]
    private \DateTime $createdAt;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Property(write: false)]
    private ?\DateTime $sentAt;
    /**
     * @ORM\Column(type="text")
     */
    #[Property(write: false)]
    private string $title;
    /**
     * @ORM\Column(type="text")
     */
    #[Property(write: false)]
    private string $content;

    public function __construct(User $sender, string $title, string $content)
    {
        $this->sender  = $sender;
        $this->title   = $title;
        $this->content = $content;

        $this->createdAt = new \DateTime();
        $sender->addMessage($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Message
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): Message
    {
        $this->content = $content;
        return $this;
    }

    public function isSent(): bool {
        return $this->sentAt !== null;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTime $sentAt): Message
    {
        $this->sentAt = $sentAt;
        return $this;
    }


}