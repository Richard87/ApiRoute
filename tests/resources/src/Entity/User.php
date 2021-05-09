<?php

namespace Richard87\ApiRoute\Tests\resources\src\Entity;

use Doctrine\Common\Collections\Collection;
use Richard87\ApiRoute\Tests\resources\src\Controller\FetchImportantMessagesController;
use Richard87\ApiRoute\Tests\resources\src\Controller\InviteController;
use Richard87\ApiRoute\Tests\resources\src\Entity\Dto\ResetPassword;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Richard87\ApiRoute\Attributes\ApiResource;
use Richard87\ApiRoute\Attributes\Property;
use Richard87\ApiRoute\Attributes\Rest;
use Richard87\ApiRoute\Attributes\CollectionRoute;
use Richard87\ApiRoute\Attributes\ApiRoute;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
#[ApiResource(summary: "User")]
#[Rest\Get(security: "isGranted('ROLE_USER')")] // GET /api/users/{id}
#[Rest\Create] // POST /api/users
#[Rest\Update] // PATCH /api/users/{id} (content type specify strategy)
#[Rest\Delete] // DELETE /api/users/{id}
#[Rest\Collection(security:"isGranted('ROLE_USER')")] // GET /api/users
#[CollectionRoute(controller: InviteController::class, security: "isGranted('ROLE_ADMIN')")] // POST /api/users/invite

#[ApiRoute(input: Message::class, output: Message::class, controller: FetchImportantMessagesController::class, path: "important-messages")] // GET /api/users{id}/important_messages
#[ApiRoute(input: ResetPassword::class, output: User::class)] // POST /api/users/reset_password //it doesn't have controller, so it must be a message
#[ApiRoute(controller: InviteController::class, security: "isGranted('ROLE_ADMIN')")] // POST /api/users/{id}/invite
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    #[Property]
    private ?int $id = null;

    /**
     * @var ArrayCollection<int, Message>|Collection
     * @ORM\OneToMany(targetEntity="Message", cascade={"persist"}, mappedBy="sender")
     *
     * Should create a endpoint like this: GET /api/users/{id}/messages[?onlySent=true|false]
     */
    #[CollectionRoute]
    #[Property]
    private ArrayCollection|Collection $messages;

    /**
     * @ORM\Column(type="string")
     */
    private string $hashedPassword;

    /**
     * @ORM\Column(type="string")
     */
    #[Property(write: true)]
    private string $name;

    /**
     * @ORM\Column(type="string")
     */
    #[Property(write: true)]
    private string $email;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Property]
    private \DateTime $createdAt;

    public function __construct(string $hashedPassword, string $name, string $email)
    {
        $this->hashedPassword = $hashedPassword;
        $this->name = $name;
        $this->email = $email;

        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }


    #[CollectionRoute] // GET /api/users/{id}/message_senders?only_sent=true/false
    /** @var ArrayCollection<int, User> */
    public function getMessageSenders(bool $onlySent = false): ArrayCollection {
        return $this->getMessages($onlySent)->map(fn(Message $m)=> $m->getSender());
    }

    public function addMessage(Message $message): void
    {
        $this->messages->add($message);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param bool $onlySent
     * @return ArrayCollection<int, Message>|Collection
     */
    public function getMessages(bool $onlySent = false): ArrayCollection|Collection
    {
        if ($onlySent) {
            return $this->messages->filter(fn(Message $m) => $m->isSent());
        }

        return $this->messages;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function setHashedPassword(string $hashedPassword): User
    {
        $this->hashedPassword = $hashedPassword;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    #[ApiRoute(security: "ROLE_ADMIN")]
    public function setName(string $name): User
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}