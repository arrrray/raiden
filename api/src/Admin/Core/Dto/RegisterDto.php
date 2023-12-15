<?php

namespace App\Admin\Core\Dto;

use App\Admin\Core\Entity\User;
use App\Admin\Core\Enum\UserType;
use Cesurapp\ApiBundle\Validator\PhoneNumber;
use Cesurapp\ApiBundle\Validator\UniqueEntity;
use Cesurapp\ApiBundle\AbstractClass\ApiDto;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegisterDto extends ApiDto
{
    #[Assert\Length(max: 180)]
    #[Assert\Email]
    #[UniqueEntity(entityClass: User::class, fields: ['email'])]
    public ?string $email = null;

    #[Assert\Country]
    public ?string $phone_country = null;

    #[PhoneNumber(regionPath: 'phone_country')]
    #[UniqueEntity(entityClass: User::class, fields: ['phone'])]
    public null|int|string $phone = null;

    #[Assert\NotNull]
    #[Assert\Choice(callback: 'getTypes')]
    public ?string $type = UserType::USER->value;

    #[Assert\Length(min: 8)]
    #[Assert\NotNull]
    public string $password;

    #[Assert\Length(min: 2, max: 50)]
    #[Assert\NotNull]
    public string $first_name;

    #[Assert\Length(min: 2, max: 50)]
    #[Assert\NotNull]
    public string $last_name;

    public static function getTypes(): array
    {
        return [UserType::USER->value];
    }

    #[Assert\Callback]
    public function callbackValidator(ExecutionContextInterface $context): void
    {
        if (!$this->email) {
            $context->getValidator()
                ->inContext($context)
                ->atPath('phone')
                ->validate($this->phone, [new Assert\NotNull(), new Assert\NotBlank()])
                ->atPath('phone_country')
                ->validate($this->phone_country, [new Assert\NotNull()]);
        } elseif (!$this->phone) {
            $context->getValidator()
                ->inContext($context)
                ->atPath('email')
                ->validate($this->email, [new NotNull(), new Assert\NotBlank()]);
        }
    }

    public function initObject(string|User $object): User
    {
        return $object
            ->setEmail($this->validated('email'))
            ->setPhone($this->validated('phone'))
            ->setPhoneCountry($this->validated('phone_country'))
            ->setType(UserType::from($this->validated('type')))
            ->setFirstName($this->validated('first_name'))
            ->setLastName($this->validated('last_name'))
            ->setLanguage($this->getRequest()->getLocale());
    }
}
