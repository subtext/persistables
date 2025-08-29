# Subtext\Persistables

A lightweight PHP library designed to abstract and unify the persistence of 
domain objects across SQL databases. Inspired by the principles of ORMs, but 
intentionally minimal and flexible, this package gives you full control over how 
data is mapped and stored in stateful services.

## ✨ Key Features

- Store class data in SQL (MySQL, MSSQL) 
- Use class and property attributes to define database-specific behavior
- Clean separation of domain logic and persistence logic
- No assumptions about schema or backend; bring your own structure

## 🧠 Core Concept

Extend your domain models from `Persistable`, and add attributes informing the 
factory how to save your data.

```php
namespace Subtext\Persistables;

#[Table(name: 'users', primaryKey: 'userId')]
class User extends Persistable
{
    #[Column(name: 'user_id')]
    protected ?int $userId = null;
    
    #[Column(name: 'user_name')]
    protected ?string $userName = null;
    
    #[Column(name: 'email_address')]
    protected ?string $email = null;
    
    /**
     * Defining an empty constructor allows the entity to be autowired for
     * dependency injection
     */
    public function __construct()
    {}
    
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    
    public function setUserId(?int $userId): void
    {
        $this->modify('userId', $userId);
    }
    
    public function getUserName(): ?string
    {
        return $this->userName;
    }
    
    public function setUserName(string $userName): void
    {
        $this->modify('userName', $userName);
    }
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): void
    {
        $this->modify('email', $email);
    }
    
    public function jsonSerialize(): mixed
    {
        return (object) [
            'userId'   => $this->getUserId(),
            'userName' => $this->getUserName(),
            'email'    => $this->getEmail(),
        ];   
    }    
}
```
