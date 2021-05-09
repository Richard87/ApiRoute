# ApiRoute

A simplified version if ApiPlatform to generate flexible api rotues and automatically infer types for OpenAPI v3.

Limited to only (automatically) serializing `jsonld`. But because of the limited scope, we can be way more flexible with how and where we use the annotation `#[ApiRoute]`.

For version 1.0 we are tightly integrated with Symfony and Doctrine to limit scope and make it as easy to program as possible.

This library provides 3 base annotations: 
- `#[ApiRoute]` - Creates a route and automatic serializes and deserializes requests and responses.
- `#[ApiResource]` - Tells ApiRoute that this is a entity with it's own ID.
- `#[Property]` - Tells ApiRoute which properties the user is allowed to read and write.

Extended RESTful Annotations to simplify API development:
- `#[Rest\Get)]`
- `#[Rest\Create]`
- `#[Rest\Update]`
- `#[Rest\Delete]`
- `#[Rest\Collection)]`

A basic route looks like this: `#[ApiRoute(input: ResetPassword::class, output: User::class, controller: RestPasswordController::class)]`.
If you omit `controller:` but have a `input:` we assume it's a Symfony Messenger event and dispatches it accordingly. 

Other events is handled "predictably", you can not use `input` or `output` when using ApiRoute on a `property`, but you could use `input` when setting it on a `method`.

To simplify things we include a few *special* variables that can be used in controllers or methods:
- `UserInterface $loggedInUser` gives you the logged in user. If it's not nullable and the user is not logged in, a AccessDenied exception is thrown.
- `RequestInterface $request` gives you the symfony Request object.

The entity manager is flushed after each request, unless `#[ApiRoute(flush: false)]` is set.

## Todo
- [ ] Generate proper OpenAPI 3.0 Spec
  - [X] Find all endpoints / generate URL's
  - [X] Map all Resources /DTOs
  - [ ] Inspect arrays/collection and find correct target object / generics
  - [ ] Map IRI's / If property is ApiResource use IRI
  - [ ] Custom object serialization (iri's, uuid's, datetime's, collections)
- [ ] Handle requests in controller
- [ ] Deserialize requests and map arguments
- [ ] Serialize responses
- [ ] Flush changes
- [ ] Add cache-warmer for OpenAPI spec
- [ ] Handle security, add Default property read/write security rules, default ApiRoute security rules


## Future scope
Hopefully in version 2.0 we can expand the scope with more integration with Laravel and other ORMs. 

## Examples:
Example:
```php
namespace Richard87\ApiRoute\Tests\resources\src\Entity;

use Richard87\ApiRoute\Tests\resources\src\Controller\FetchImportantMessagesController;
use Richard87\ApiRoute\Tests\resources\src\Controller\InviteController;
use Richard87\ApiRoute\Tests\resources\src\Entity\Dto\ResetPassword;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
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
     * @var ArrayCollection<int, Message>|PersistentCollection
     * @ORM\OneToMany(targetEntity="Message", cascade={"persist"}, mappedBy="sender")
     *
     * Should create a endpoint like this: GET /api/users/{id}/messages[?onlySent=true|false]
     */
    #[CollectionRoute]
    #[Property]
    private ArrayCollection|PersistentCollection $messages;

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
     * @return ArrayCollection<int, Message>|PersistentCollection
     */
    public function getMessages(bool $onlySent = false): ArrayCollection|PersistentCollection
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
```
### Create new message
```http request
POST /api/messages/
Content-Type: application/json
accept: application/ld+json

{
    "user": "/api/users/1",
    "subject": "Do you want to be friends?",
    "content": "Hello world!"
}

```